<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionPayout;
use App\Models\User;
use App\Models\UserBank;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashFreeController extends Controller
{

    //1. Create CashFree Payment Token
    //2. CashFree Payment Webhook
    //3. Verify CashFree Payment Signature
    //4. Authorize CashFree Payout
    //5. Add Beneficiary for Payout
    //6. Remove beneficiary from payout
    //7. Request CashFree transfer
    //8. Get CashFree Transfer Status
    //9. CashFree Transfer Webhook



    //1. Create cashfree token
	public function create_cashfree_token(Request $request){
		$userId=$request->userId;
		$cashfreeParam['orderId']=$request->orderId;
		$cashfreeParam['orderAmount']=$request->orderAmount;
		$cashfreeParam['orderCurrency']="INR";

		$setting=Setting::first();
		$appId=$setting->cf_app_id;
		$secretKey=$setting->cf_secret_key;
	
		$post_data = json_encode($cashfreeParam, JSON_UNESCAPED_SLASHES);

		$url = "https://api.cashfree.com/api/v2/cftoken/order";

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$headers=['Content-Type: application/json',
					'x-client-id: '.$appId,
					'x-client-secret: '.$secretKey];
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		$response = curl_exec($ch);

		$responseData=json_decode($response,JSON_UNESCAPED_SLASHES);

		if($responseData['status']=="OK"){
			DB::beginTransaction();
			try{
				$transactionCount=Transaction::count()+1;
				$txnId="TXNID".$transactionCount;
		
				$transactionData=[];
				$transactionData['order_id']=$request->orderId;
				$transactionData['txn_id']=$txnId;
				$transactionData['user_id']=$userId;
				$transactionData['txn_mode']="CASH FREE";
				$transactionData['txn_type']="ADD";
				$transactionData['txn_status']="PENDING";
				$transactionData['txn_title']="Add Money to wallet";
				$transactionData['txn_sub_title']="Processing transaction";
				$transactionData['txn_admin_title']="Add Money by User";
				$transactionData['txn_message']="Wait for the transaction status to change, if it doesn't changes contact support";
				$transactionData['txn_amount']=$request->orderAmount;
				$transactionData['closing_balance']=0;
		
				$transaction=Transaction::create($transactionData);
				DB::commit();
				return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$responseData]);
			}catch(Exception $e){
				DB::rollBack();
                return $e;
			}
		}

		return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
	}


    //2. Cashfree webhook
	public function cashfree_webhook(Request $request){

		$setting=Setting::first();
		$secretKey=$setting->cf_secret_key;

		$orderId = $request->orderId;
		$orderAmount = $request->orderAmount;
		$referenceId = $request->referenceId;
		$txStatus = $request->txStatus;
		$paymentMode = $request->paymentMode;
		$txMsg = $request->txMsg;
		$txTime = $request->txTime;
		$signature = $request->signature;
		$data = $orderId.$orderAmount.$referenceId.$txStatus.$paymentMode.$txMsg.$txTime;
		$hash_hmac = hash_hmac('sha256', $data, $secretKey, true) ;
		$computedSignature = base64_encode($hash_hmac);
		if ($signature == $computedSignature) {

			DB::beginTransaction();

			try{
				$transaction=Transaction::where('order_id',$orderId)->first();
			if($transaction->txn_status=="PENDING"){
				$user=User::where('id',$transaction->user_id)->first();
				$admin=Admin::first();
				$userBalance=$user->wallet_balance;
				$adminBalance=$admin->wallet_balance;
				if($txStatus=="SUCCESS"){
					//Transaction is successful
					$transaction->txn_mode=$paymentMode;
					$transaction->reference_id=$referenceId;
					$transaction->txn_status="SUCCESS";
					$transaction->txn_sub_title="Money added successfully";
					$transaction->txn_message="";
					$transaction->closing_balance=$userBalance+$orderAmount;
					$user->wallet_balance=$userBalance+$orderAmount;
					$admin->wallet_balance=$adminBalance-$orderAmount;
					$transaction->save();
					$user->save();
					$admin->save();
				}else if($txStatus=="FAILED"){
					//Transaction is failed
					$transaction->txn_mode=$paymentMode;
					$transaction->reference_id=$referenceId;
					$transaction->txn_status="FAILED";
					$transaction->txn_sub_title="Failed to add money";
					$transaction->txn_message="If the amount is deducted, it will be refunded back to account in 3-4 days.";
					$transaction->save();


				}else if($txStatus=="CANCELLED"){
					//Transaction is cancelled
					$transaction->txn_mode=$paymentMode;
					$transaction->reference_id=$referenceId;
					$transaction->txn_status="CANCELLED";
					$transaction->txn_sub_title="Transaction cancelled by user";
					$transaction->txn_message="";
					$transaction->save();

				}else {
					//Transaction is pending
	
				}

				if($setting->add_money_notification==1){
					if($transaction->txn_status=="SUCCESS"){
						$title="Money added successfully.";
						$message="A amount of Rs ".$orderAmount.' is successfully added by'.$user->user_name;
					}else if($transaction->txn_status=="PENDING"){
						$title="Add Money Pending";
						$message="Add money of Rs ".$orderAmount.' is pending by'.$user->user_name;
					}else{
						$title="Add Money Failed";
						$message="Add money of Rs ".$orderAmount.' has failed by'.$user->user_name;
					}
					
	
					$tokenList=[];
					array_push($tokenList,$admin->token);
					$this->notification($tokenList,$title,$message,$setting->gcm_auth);
				}
			}

			DB::commit();

			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);

			}catch(Exception $e){
				DB::rollBack();
				return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
			}
			
	   } else {
		  // Reject this call
		  return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
		}
	}

	//3. Verify Cashfree Signature
	public function verify_cashfree_signature(Request $request){

		$setting=Setting::first();
		$secretKey=$setting->cf_secret_key;

		$orderId = $request->orderId;
 		$orderAmount = $request->orderAmount;
 		$referenceId = $request->referenceId;
 		$txStatus = $request->txStatus;
 		$paymentMode = $request->paymentMode;
 		$txMsg = $request->txMsg;
 		$txTime = $request->txTime;
 		$signature = $request->signature;
 		$data = $orderId.$orderAmount.$referenceId.$txStatus.$paymentMode.$txMsg.$txTime;
 		$hash_hmac = hash_hmac('sha256', $data, $secretKey, true) ;
 		$computedSignature = base64_encode($hash_hmac);
 		if ($signature == $computedSignature) {
			DB::beginTransaction();

			try{
				$transaction=Transaction::where('order_id',$orderId)->first();
			if($transaction->txn_status=="PENDING"){
				$user=User::where('id',$transaction->user_id)->first();
				$admin=Admin::first();
				$userBalance=$user->wallet_balance;
				$adminBalance=$admin->wallet_balance;
				if($txStatus=="SUCCESS"){
					//Transaction is successful
					$transaction->txn_mode=$paymentMode;
					$transaction->reference_id=$referenceId;
					$transaction->txn_status="SUCCESS";
					$transaction->txn_sub_title="Money added successfully";
					$transaction->txn_message="";
					$transaction->closing_balance=$userBalance+$orderAmount;
					$user->wallet_balance=$userBalance+$orderAmount;
					$admin->wallet_balance=$adminBalance+$orderAmount;
					$transaction->save();
					$user->save();
					$admin->save();
				}else if($txStatus=="FAILED"){
					//Transaction is failed
					$transaction->txn_mode=$paymentMode;
					$transaction->reference_id=$referenceId;
					$transaction->txn_status="FAILED";
					$transaction->txn_sub_title="Failed to add money";
					$transaction->txn_message="If the amount is deducted, it will be refunded back to account in 3-4 days.";
					$transaction->save();


				}else if($txStatus=="CANCELLED"){
					//Transaction is cancelled
					$transaction->txn_mode=$paymentMode;
					$transaction->reference_id=$referenceId;
					$transaction->txn_status="FAILED";
					$transaction->txn_sub_title="Transaction cancelled by user";
					$transaction->txn_message="";
					$transaction->save();

				}else {
					//Transaction is pending
	
				}

				if($setting->add_money_notification==1){
					if($transaction->txn_status=="SUCCESS"){
						$title="Money added successfully.";
						$message="A amount of Rs ".$orderAmount.' is successfully added by'.$user->user_name;
					}else if($transaction->txn_status=="PENDING"){
						$title="Add Money Pending";
						$message="Add money of Rs ".$orderAmount.' is pending by'.$user->user_name;
					}else{
						$title="Add Money Failed";
						$message="Add money of Rs ".$orderAmount.' has failed by'.$user->user_name;
					}
					
	
					$tokenList=[];
					array_push($tokenList,$admin->token);
					$this->notification($tokenList,$title,$message,$setting->gcm_auth);
				}
			}
		

			DB::commit();

			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);

			}catch(Exception $e){
				DB::rollBack();
				return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
			}
		} else {
   		// Reject this call
 		}
	}

	//4. Authorize CashFree Payout
	public function get_cfp_auth_key(){
		$setting=Setting::first();
		$appId=$setting->cfp_app_id;
		$secretKey=$setting->cfp_secret_key;
		$authExpiry=$setting->cfp_auth_expiry;
		$auth=$setting->cfp_auth;

		$urlVerify="https://payout-api.cashfree.com/payout/v1/verifyToken";

		$chVerify = curl_init($urlVerify);
		curl_setopt($chVerify, CURLOPT_POST, 1);
		curl_setopt($chVerify, CURLOPT_RETURNTRANSFER, true); 
		$headersVerify=['Content-Type: application/json',
					'Authorization: Bearer '.$auth];
		curl_setopt($chVerify, CURLOPT_HTTPHEADER, $headersVerify); 
		$responseVerify = curl_exec($chVerify);
	
		$responseDataVerify=json_decode($responseVerify,JSON_UNESCAPED_SLASHES);

		if($responseDataVerify['subCode']==200&&$responseDataVerify['status']=="SUCCESS"){
			$resultData=[];
			$resultData['auth_key']=$setting->cfp_auth;
			return $resultData;
		}

		$url = "https://payout-api.cashfree.com/payout/v1/authorize";

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$headers=['Content-Type: application/json',
					'x-client-id: '.$appId,
					'x-client-secret: '.$secretKey];
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		$response = curl_exec($ch);

		$responseData=json_decode($response,JSON_UNESCAPED_SLASHES);


		if($responseData['subCode']==200&&$responseData['status']=="SUCCESS"){
			//Save the token to database and expiry time
			$setting->cfp_auth=$responseData['data']['token'];
			$setting->cfp_auth_expiry=$responseData['data']['expiry'];
			$setting->save();
		}
		

		$resultData=[];
		$resultData['auth_key']=$setting->cfp_auth;

		return $resultData;
	}

	//5. Add Beneficiary for Payout
	public function add_cfp_beneficiary(Request $request){
		$beneficiaryData=[];
		$beneficiaryData['beneId']=$request->beneId;
		$beneficiaryData['bankAccount']=$request->bank_account;
		$beneficiaryData['ifsc']=$request->ifsc;
		$beneficiaryData['name']=$request->name;


		$user=User::where('id',$request->beneId)->first();

		if($user->email==null||$user->address==null||$user->city==null||$user->state==null||$user->pincode==null){
			return response()->json(['status'=>'FAILURE','code'=>'FC_02','data'=>null]);
		}

		$beneficiaryData['email']=$user->email;
		$beneficiaryData['phone']=$user->phone;
		$beneficiaryData['address1']=$user->address;
		$beneficiaryData['city']=$user->city;
		$beneficiaryData['state']=$user->state;
		$beneficiaryData['pincode']=$user->pincode;


		$userBank=UserBank::where('user_id',$request->beneId)->first();

		if($userBank){
			$removedBeneData=$this->remove_beneficiary($request->beneId);
		}

		$authKeyData=$this->get_cfp_auth_key();
		$authKey=$authKeyData['auth_key'];
        

		$post_data = json_encode($beneficiaryData, JSON_UNESCAPED_SLASHES);

		$url = "https://payout-api.cashfree.com/payout/v1/addBeneficiary";

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$headers=['Content-Type: application/json',
					'Authorization: Bearer '.$authKey];
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		$response = curl_exec($ch);

		$responseData=json_decode($response,JSON_UNESCAPED_SLASHES);
        

        if($responseData['subCode']==200&&$responseData['status']=="SUCCESS"){
			if($userBank){
				$userBank->account_number=$request->bank_account;
				$userBank->account_ifsc=$request->ifsc;
				$userBank->account_name=$request->name;
				$userBank->save();
			}else{
				$userBankData=[];
                $userBankData['user_id']=$request->beneId;
                $userBankData['account_number']=$request->bank_account;
                $userBankData['account_ifsc']=$request->ifsc;
                $userBankData['account_name']=$request->name;
                
                $userBank=UserBank::create($userBankData);
			}
                
		    return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$userBank]);
        }

		return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
	}


	//6. Remove beneficiary
	public function remove_beneficiary($beneId){
		$beneficiaryData=[];
		$beneficiaryData['beneId']=$beneId;
		$authKeyData=$this->get_cfp_auth_key();
		$authKey=$authKeyData['auth_key'];

		$post_data = json_encode($beneficiaryData, JSON_UNESCAPED_SLASHES);

		$url = "https://payout-api.cashfree.com/payout/v1/removeBeneficiary";

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$headers=['Content-Type: application/json',
					'Authorization: Bearer '.$authKey];
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		$response = curl_exec($ch);

		$responseData=json_decode($response,JSON_UNESCAPED_SLASHES);

		return $responseData;
	}


	//7. Request CashFree Transfer
	public function request_cashfree_transfer(Request $request){
        $beneId=$request->beneId;
        $amount=$request->amount;
        $transferId=$request->transferId;

        $transferData=[];
        $transferData['beneId']=$beneId;
        $transferData['amount']=$amount;
        $transferData['transferId']=$transferId;

        $user=User::where('id',$beneId)->first();
		$userBank=UserBank::where('user_id',$beneId)->first();
        $userBalance=$user->wallet_balance;
        $lockedBalance=$user->locked_balance;

		if($user->kyc_status!="VERIFIED"){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_03','data'=>null]);
		}

        if($userBalance-$lockedBalance>=$amount){

        DB::beginTransaction();

        try{
            $transactionCount=Transaction::count()+1;
		$txnId="TXNID".$transactionCount;

        $admin=Admin::first();
        $adminBalance=$admin->wallet_balance;

        $transactionData=[];
        $transactionData['order_id']=$transferId;
        $transactionData['txn_id']=$txnId;
        $transactionData['user_id']=$beneId;
        $transactionData['txn_mode']="CASH FREE";
        $transactionData['txn_type']="WITHDRAW";
        $transactionData['txn_status']="PENDING";
        $transactionData['txn_title']="Withdraw money from wallet";
        $transactionData['txn_sub_title']="Withdrawal request pending";
        $transactionData['txn_admin_title']="Withdraw money by User";
        $transactionData['txn_message']="Wait for the transaction status to change, if it doesn't changes contact support";
        $transactionData['txn_amount']=$amount;
        $transactionData['closing_balance']=$userBalance-$amount;
        $user->wallet_balance=$userBalance-$amount;
        $admin->wallet_balance=$adminBalance-$amount;
        
        $transaction=Transaction::create($transactionData);
        $user->save();
        $admin->save();

		$payoutTxnData=[];
		$payoutTxnData['txn_id']=$txnId;
		$payoutTxnData['user_id']=$beneId;
		$payoutTxnData['account_name']=$userBank->account_name;
		$payoutTxnData['account_ifsc']=$userBank->account_ifsc;
		$payoutTxnData['account_number']=$userBank->account_number;

		$payoutTransaction=TransactionPayout::create($payoutTxnData);
		$setting=Setting::first();

		if($amount>=$setting->max_withdrawal){
			$transaction->txn_status="QUEUED";
            $transaction->txn_sub_title="Withdrawal queued for approval";
            $transaction->txn_message="It may take upto 24 hours. Contact support in case of any issue.";
            $transaction->save();

			if($setting->withdrawal_notification==1){
				$title="Queued Withdrawal";
				$message="A withdrawal of Rs ".$amount.' is waiting for approval.';

				$tokenList=[];
				array_push($tokenList,$admin->token);
				$this->notification($tokenList,$title,$message,$setting->gcm_auth);
			}

			return response()->json(['status'=>'SUCCESS','code'=>'SC_02','data'=>$transaction]);
		}

        $authKeyData=$this->get_cfp_auth_key();
		$authKey=$authKeyData['auth_key'];

		$post_data = json_encode($transferData, JSON_UNESCAPED_SLASHES);

		$url = "https://payout-api.cashfree.com/payout/v1.2/requestTransfer";

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$headers=['Content-Type: application/json',
					'Authorization: Bearer '.$authKey];
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		$response = curl_exec($ch);

		$responseData=json_decode($response,JSON_UNESCAPED_SLASHES);

        if($responseData['subCode']==200&&$responseData['status']=="SUCCESS"){
            $transaction->txn_status="SUCCESS";
            $transaction->txn_sub_title="Amount transferred to bank successfully";
            $transaction->txn_message="In case of any issue, Contact support";
            $transaction->reference_id=$responseData['data']['referenceId'];
            $transaction->utr=$responseData['data']['utr'];
            $transaction->save();
        }else if(($responseData['subCode']==201&&$responseData['status']=="SUCCESS")||$responseData['status']=="PENDING"){
            $transaction->reference_id=$responseData['data']['referenceId'];
            $transaction->utr=$responseData['data']['utr'];
            $transaction->save();
        }else{
            $transaction->txn_status="FAILED";
            $transaction->txn_sub_title="Failed to transfer amount";
            $transaction->txn_message="In case of any issue, Contact support";
            $transaction->closing_balance=$transaction->closing_balance+$amount;
            $user->wallet_balance=$user->wallet_balance+$amount;
            $admin->wallet_balance=$admin->wallet_balance+$amount;
            $transaction->save();
            $user->save();
            $admin->save();
        }

		if($setting->withdrawal_notification==1){
			if($transaction->txn_status=="SUCCESS"){
				$title="Successful Withdrawal";
				$message="Amount of Rs ".$amount.' is withdrawn successfully.';
			}else if($transaction->txn_status=="PENDING"){
				$title="Pending Withdrawal";
				$message="Amount of Rs ".$amount.' is pending withdrawal.';
			}else{
				$title="Failed Withdrawal";
				$message="Failed to withdraw amount of Rs ".$amount;
			}

			$tokenList=[];
			array_push($tokenList,$admin->token);
			$this->notification($tokenList,$title,$message,$setting->gcm_auth);
		}

        DB::commit();
            
        return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
        }catch(Exception $e){
            DB::rollBack();
		    return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
        }
        }else{
		    return response()->json(['status'=>'FAILURE','code'=>'FC_02','data'=>null]);
        }
	}


	//8. Get CFP Transfer Status
	public function get_cfp_transfer_status(Request $request){
        $setting=Setting::first();
        DB::beginTransaction();

        try{

        $authKeyData=$this->get_cfp_auth_key();
		$authKey=$authKeyData['auth_key'];

        $transaction=Transaction::where('order_id',$request->transfer_id)->first();

        $url = "https://payout-api.cashfree.com/payout/v1.1/getTransferStatus?referenceId=".$request->reference_id."&transferId=".$request->transfer_id;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$headers=['Content-Type: application/json',
					'Authorization: Bearer '.$authKey];
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		$response = curl_exec($ch);

		$responseData=json_decode($response,JSON_UNESCAPED_SLASHES);
            
        if($transaction->txn_status=="PENDING"){
            if($responseData['subCode']==200&&$responseData['status']=="SUCCESS"){
                if($responseData['data']['transfer']['status']=="SUCCESS"){$transaction->txn_status="SUCCESS";
                    $amount=$transaction->txn_amount;
					$user=User::where('id',$transaction->user_id)->first();
					
					$transaction->txn_status="SUCCESS";
                    $transaction->txn_sub_title="Amount transferred to bank successfully";
                    $transaction->txn_message="In case of any issue, Contact support";
                    $transaction->save();

					$title="Successful Withdrawal";
					$message="Amount of Rs ".$amount.' is withdrawn successfully.';
			

					$tokenList=[];
					array_push($tokenList,$user->token);
					$this->notification($tokenList,$title,$message,$setting->gcm_auth);

                }else if($responseData['data']['transfer']['status']=="FAILED"){

                    $user=User::where('id',$transaction->user_id)->first();
                    $admin=Admin::first();

                    $amount=$responseData['data']['transfer']['amount'];

                    $transaction->txn_status="FAILED";
                    $transaction->txn_sub_title="Failed to transfer amount";
                    $transaction->txn_message="In case of any issue, Contact support";
                    $transaction->closing_balance=$transaction->closing_balance+$amount;
                    $user->wallet_balance=$user->wallet_balance+$amount;
                    $admin->wallet_balance=$admin->wallet_balance+$amount;
                    $transaction->save();
                    $user->save();
                    $admin->save();

					$title="Failed Withdrawal";
					$message="Failed to withdraw amount of Rs ".$amount;
			

					$tokenList=[];
					array_push($tokenList,$user->token);
					$this->notification($tokenList,$title,$message,$setting->gcm_auth);

                }
            }
        }
        DB::commit();
        return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);

        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>$e->getMessage()]);
        }

	}

    
    //9. CashFree Transfer Webhook
    public function cfp_transfer_webhook(Request $request){
        $setting=Setting::first();
		$secretKey=$setting->cfp_secret_key;

        $data = $request->all();
        $signature = $data["signature"];
        unset($data["signature"]);
        ksort($data);
        $postData = "";
        foreach ($data as $key => $value){
            if (strlen($value) > 0) {
            $postData .= $value;
            }
        }
        $hash_hmac = hash_hmac('sha256', $postData, $secretKey, true) ;
        
        $computedSignature = base64_encode($hash_hmac);
        if ($signature == $computedSignature) {

            DB::beginTransaction();

            try{
				if($data['event']=="LOW_BALANCE_ALERT"){
					return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
				}else if($data['event']=="CREDIT_CONFIRMATION"){
					return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
				}else if($data['event']=="TRANSFER_SUCCESS"||$data['event']=="TRANSFER_ACKNOWLEDGED"){
					//Transaction successful
					$transaction=Transaction::where('order_id',$data['transferId'])->first();
					if($transaction['txn_status']=="PENDING"){
					$amount=$transaction->txn_amount;
					$user=User::where('id',$transaction->user_id)->first();

					$transaction->txn_status="SUCCESS";
					$transaction->txn_sub_title="Amount transferred to bank successfully";
					$transaction->txn_message="In case of any issue, Contact support";
					$transaction->save();

					$title="Successful Withdrawal";
					$message="Amount of Rs ".$amount.' is withdrawn successfully.';
			

					$tokenList=[];
					array_push($tokenList,$user->token);
					$this->notification($tokenList,$title,$message,$setting->gcm_auth);
					}
				}else{
					//Failed transaction
					$transaction=Transaction::where('order_id',$data['transferId'])->first();
					if($transaction['txn_status']=="PENDING"){

					$user=User::where('id',$transaction->user_id)->first();
					$admin=Admin::first();

					$amount=$transaction->txn_amount;

					$transaction->txn_status="FAILED";
					$transaction->txn_sub_title="Failed to transfer amount";
					$transaction->txn_message="In case of any issue, Contact support";
					$transaction->closing_balance=$transaction->closing_balance+$amount;
					$user->wallet_balance=$user->wallet_balance+$amount;
					$admin->wallet_balance=$admin->wallet_balance+$amount;
					$transaction->save();
					$user->save();
					$admin->save();

					$title="Failed Withdrawal";
					$message="Failed to withdraw amount of Rs ".$amount;
			

					$tokenList=[];
					array_push($tokenList,$user->token);
					$this->notification($tokenList,$title,$message,$setting->gcm_auth);
					}
				}
					
                DB::commit();
                return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
            }catch(Exception $e){
                DB::rollBack();
                return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
            }
            
        } else {
            // Reject this call
            return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
        }
    }


	public function request_cashfree_admin_transfer(Request $request){
		$txnId=$request->txn_id;
		$transaction=Transaction::where('txn_id',$txnId)->first();
		$setting=Setting::first();
		$user=User::where('id',$transaction->user_id)->first();
		$admin=Admin::where('id',1)->first();
		$amount=$transaction->txn_amount;

		$transferData=[];
        $transferData['beneId']=$transaction->user_id;
        $transferData['amount']=$transaction->txn_amount;
        $transferData['transferId']=$transaction->order_id;
		if($transaction->txn_type=="WITHDRAW"&&$transaction->txn_status=="QUEUED"){
			DB::beginTransaction();

			try{
	
			$authKeyData=$this->get_cfp_auth_key();
			$authKey=$authKeyData['auth_key'];
	
			$post_data = json_encode($transferData, JSON_UNESCAPED_SLASHES);
	
			$url = "https://payout-api.cashfree.com/payout/v1.2/requestTransfer";
	
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			$headers=['Content-Type: application/json',
						'Authorization: Bearer '.$authKey];
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
			$response = curl_exec($ch);
	
			$responseData=json_decode($response,JSON_UNESCAPED_SLASHES);
	
			if($responseData['subCode']==200&&$responseData['status']=="SUCCESS"){
				$transaction->txn_status="SUCCESS";
				$transaction->txn_sub_title="Amount transferred to bank successfully";
				$transaction->txn_message="In case of any issue, Contact support";
				$transaction->reference_id=$responseData['data']['referenceId'];
				$transaction->utr=$responseData['data']['utr'];
				$transaction->save();
			}else if(($responseData['subCode']==201&&$responseData['status']=="SUCCESS")||$responseData['status']=="PENDING"){
				$transaction->reference_id=$responseData['data']['referenceId'];
				$transaction->utr=$responseData['data']['utr'];
				$transaction->save();
			}else{
				$transaction->txn_status="FAILED";
				$transaction->txn_sub_title="Failed to transfer amount";
				$transaction->txn_message="In case of any issue, Contact support";
				$transaction->closing_balance=$transaction->closing_balance+$amount;
				$user->wallet_balance=$user->wallet_balance+$amount;
				$admin->wallet_balance=$admin->wallet_balance+$amount;
				$transaction->save();
				$user->save();
				$admin->save();
			}
	
			if($setting->withdrawal_notification==1){
				if($transaction->txn_status=="SUCCESS"){
					$title="Successful Withdrawal";
					$message="Amount of Rs ".$amount.' is withdrawn successfully.';
				}else if($transaction->txn_status=="PENDING"){
					$title="Pending Withdrawal";
					$message="Amount of Rs ".$amount.' is pending withdrawal.';
				}else{
					$title="Failed Withdrawal";
					$message="Failed to withdraw amount of Rs ".$amount;
				}
	
				$tokenList=[];
				array_push($tokenList,$user->token);
				$this->notification($tokenList,$title,$message,$setting->gcm_auth);
			}
	
			DB::commit();
				
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
			}catch(Exception $e){
				DB::rollBack();
				return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
			}
		}else{
			return response()->json(['status'=>'FAILURE','code'=>'FC_02','data'=>null]);
		}
		
	}


	public function notification($tokenList, $title, $message, $auth)
	{
		$fcmUrl = 'https://fcm.googleapis.com/fcm/send';
		// $token=$token;

		$notification = [
			'title' => $title,
			'body' => $message,
			'sound' => true,
		];

		$extraNotificationData = ["message" => $notification];

		$fcmNotification = [
			'registration_ids' => $tokenList, //multple token array
			// 'to'        => $token, //single token
			'notification' => $notification,
			'data' => $extraNotificationData
		];

		$headers = [
			'Authorization: key=' . $auth,
			'Content-Type: application/json'
		];


		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $fcmUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
		$result = curl_exec($ch);
		curl_close($ch);

		return true;
	}


	public function refund_transaction(Request $request){
		$txnId=$request->txn_id;
		$froceRefund=$request->force_refund;

		$transaction=Transaction::where('txn_id',$txnId)->first();

		if($transaction&&$transaction->txn_type=="ADD"&&$transaction->txn_status=="SUCCESS"){
			$user=User::where('id',$transaction->user_id)->first();
			$admin=Admin::first();
			if(!$froceRefund&&$user->wallet_balance<$transaction->txn_amount){
				//User balance is not sufficient for refund, Force refund is disabled.
				return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
			}else if($froceRefund&&$user->wallet_balance<$transaction->txn_amount){
				//Force update is enabled and user balance is insufficient.
				$transaction->txn_status="REFUNDED";
				$transaction->txn_sub_title="Refund proceeded for the amount.";
				$transaction->txn_message="Amount will be refunded to the original source, it may take 7-9 days. Contact in case of any issue.";
				$transaction->closing_balance=$transaction->closing_balance-$transaction->txn_amount;
				$user->wallet_balance=0;
				$admin->wallet_balance=$admin->wallet_balance-$transaction->txn_amount;
				$transaction->save();
				$user->save();
				$admin->save();
			}else{
				//User balance is sufficient for refund.
				$transaction->txn_status="REFUNDED";
				$transaction->txn_sub_title="Refund proceeded for the amount.";
				$transaction->txn_message="Amount will be refunded to the original source, it may take 7-9 days. Contact in case of any issue.";
				$transaction->closing_balance=$transaction->closing_balance-$transaction->txn_amount;
				$user->wallet_balance=$user->wallet_balance-$transaction->txn_amount;
				$admin->wallet_balance=$admin->wallet_balance-$transaction->txn_amount;
				$transaction->save();
				$user->save();
				$admin->save();
			}
		}
	}

}
