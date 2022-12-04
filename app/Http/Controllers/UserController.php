<?php

namespace App\Http\Controllers;

use App\Models\BumperTicket;
use App\Models\CurrentGame;
use App\Models\Game;
use App\Models\GameClaim;
use App\Models\GameJoin;
use App\Models\GameTicket;
use App\Models\PlayedGame;
use App\Models\Prize;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\TransactionPayout;
use App\Models\User;
use App\Models\UserBank;
use App\Models\UserKyc;
use App\Models\UserReferral;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Classes\PaytmChecksum;
use App\Models\Admin;
use App\Models\BumperPrize;
use App\Models\CacheHistory;
// use App\Models\CacheHistory;
use App\Models\PaymentSetting;
use App\Models\SheetClaim;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDO;
use phpDocumentor\Reflection\DocBlock\Tags\See;
use PhpParser\Builder\Trait_;

class UserController extends Controller
{

	//User Adds Money +AW
	//User Withdraws -AW
	//User Purchase Ticket +AW
	//Prize Claim -AW
	//Refund Tickets -AW
	//Cancel Game - No change
	//Add Money by admin -AW
	//Withdraw money from user +AW

	//1. Send Otp
	//2. Verify Otp
	//3. Register
	//4. Update Token
	//5. Update Profile
	//6. Get my tickets
	//7. Get User Profile
	//8. Find next game time
	//9. Get prizes
	//10. Get leaderboard (Last Game, Monthly, All Time)
	//11. Get All Tickets
	//12. Purchase Ticket
	//13. Add Transaction
	//14. Get Bumper Tickets
	//15. Get all transactions
	//16. Get Game Board data
	//17. Join Game
	//18. Claim Prize
	//19. Get Bumper Game Details
	//20. Update Transaction Status
	//21. Save Bank
	//22. Get Bank

	//23. Submit KYC Request
	//24. Get Upcoming Tickets
	//25. Get Settings
	//26. Get Informations
	//27. Get Ticket Numbers purchased
	//28. Get My Ticket Numbers
	//29. Get Transaction by ID
	//30. Add Money Webhook
	//31. Check add money status
	//32. Submit withdrawal request
	//33. Withdrawal Request Webhook
	//34. Check Withdrawal Status
	//35. Get my KYC
	//36. Get Homepage Details
	//39. Get Transaction Payout



	//User Api (UPI Payment)
	//1. create UPI order
	//2. update UPI status

	//User Api (Manual Payment)
	//1. submit payment request
	//2. cancel payment request

	//User Api (Withdrawal Request)
	//1. submit withdrawal request
	//2. cancel withdrawal request


	// public function get_cache_details(){
	// 	$cacheHistory=CacheHistory::first();

	// 	$now=new Carbon();

	// 	$cacheHistory['datetime']=$now->toDateTimeString();

	// 	return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $cacheHistory]);
	// }


	public function get_verison_detail(){
		$setting=Setting::select('app_version')
						->first();

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $setting]);
	}


	//get cache meta data
	public function get_metadata(){
		$cacheHistory=CacheHistory::first();

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $cacheHistory]);
	}

	//get sold gamtime tickets.
	public function get_sold_gametime_tickets($gameDateTime){
		$gameTickets=GameTicket::leftJoin('users','users.id','game_tickets.user_id')
							->where('game_datetime',$gameDateTime)
							->select('game_tickets.ticket_number','game_tickets.user_id','users.user_name','users.user_profile')
							->get();

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $gameTickets]);
	}

	//get all games
	public function get_all_games(){
		$currentGame=CurrentGame::first();

		$normalGames=Game::where('bumper',0)
						->where('game_datetime','>=',$currentGame->game_datetime)
						->orderBy('game_datetime','ASC')
						->get();

		foreach($normalGames as $normalGame){
				$normalGameCarbon=new Carbon($normalGame->game_datetime);
				$normalGameCarbon->subMinutes($normalGame->booking_close_minutes);
				$normalGame['booking_close_time']=$normalGameCarbon->toDateTimeString();
		}

		$bumperGames=Game::where('bumper',1)
						->where('game_datetime','>=',$currentGame->game_datetime)
						->orderBy('game_datetime','ASC')
						->get();

		foreach($bumperGames as $bumperGame){
			$bumperGameCarbon=new Carbon($bumperGame->game_datetime);
			$bumperGameCarbon->subMinutes($bumperGame->booking_close_minutes);
			$bumperGame['booking_close_time']=$bumperGameCarbon->toDateTimeString();
		}

		$gameData=[];
		$gameData['normal_game']=$normalGames;
		$gameData['bumper_game']=$bumperGames;

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $gameData]);
	}

	//get all prizes
	public function get_all_prizes(){
		$prizes=Prize::where('status',1)->get();
		$bumperPrizes=BumperPrize::where('status',1)->get();

		$prizeData=[];
		$prizeData['normal_prize']=$prizes;
		$prizeData['bumper_prize']=$bumperPrizes;

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $prizeData]);
	}

	public function get_homepage_data($userId){
		$now=new Carbon();

		$currentGame=CurrentGame::first();

		if($currentGame->bumper==1){
			if($currentGame->game_status=="COMPLETED"||$currentGame->game_status=="NEW"){
				$bumperGame=Game::where('game_datetime','>=',$now)
						->where('type','BUMPER')
						->orderBy('game_datetime','ASC')
						->first();
			}else{
				$bumperGame=Game::where('game_datetime','>=',$currentGame->game_datetime)
							->where('type','BUMPER')
							->orderBy('game_datetime','ASC')
							->first();
			}
			

			$normalGame=Game::where('game_datetime','>=',$now)
						->where('type','NORMAL')
						->orderBy('game_datetime','ASC')
						->first();
		}else{
			$bumperGame=Game::where('game_datetime','>=',$now)
						->where('type','BUMPER')
						->orderBy('game_datetime','ASC')
						->first();


			if($currentGame->game_status=="COMPLETED"||$currentGame->game_status=="NEW"){
				$normalGame=Game::where('game_datetime','>=',$now)
							->where('type','NORMAL')
							->orderBy('game_datetime','ASC')
							->first();
			}else{
				$normalGame=Game::where('game_datetime','>=',$currentGame->game_datetime)
							->where('type','NORMAL')
							->orderBy('game_datetime','ASC')
							->first();
			}
		}

		

		if($bumperGame){
			$bumperGameCarbon=new Carbon($bumperGame->game_datetime);
			$bumperGameCarbon->subMinutes($bumperGame->booking_close_minutes);
			$bumperGame['booking_close_time']=$bumperGameCarbon->toDateTimeString();
		}

		if($normalGame){
			$normalGameCarbon=new Carbon($normalGame->game_datetime);
			$normalGameCarbon->subMinutes($normalGame->booking_close_minutes);
			$normalGame['booking_close_time']=$normalGameCarbon->toDateTimeString();
		}

		

		$data=[];
		if($currentGame->game_status!="COMPLETED"){
			$gameDateTime=$currentGame->game_datetime;

			$tickets=GameTicket::where('user_id',$userId)
							->where('game_datetime','>=',$gameDateTime)
							// ->union($ticketsGameDay)
							->orderBy('game_datetime','ASC')
							->get()->groupBy(['game_date','game_time']);
	
	
			foreach($tickets as $datewise){
				$count=0;
				$date="";
				foreach($datewise as $timewise){
					if(count($data)<2){
						$count=count($timewise);
						$date=$timewise[0]['game_date'].' '.$timewise[0]['game_time'];
						$tktData=[];
						$tktData['datetime']=$date;
						$tktData['count']=$count;
						array_push($data,$tktData);
					}
				}
			}

			usort($data, function($a, $b){
				return strcmp($a['datetime'], $b['datetime']);
			});


			$date=$now->toDateString();
			$time=$now->toTimeString();

			$currentGame['date']=$date;
			$currentGame['time']=$time;
			$currentGame['datetime']=$now->toDateTimeString();
		}else{
			$currentGame=null;
		}

		$user=User::where('id',$userId)
					->first();

		$userData=[];
		$userData['wallet_balance']=$user->wallet_balance;
		$userData['locked_balance']=$user->locked_balance;
		$userData['is_blocked']=$user->is_blocked;


		$result=[];
		$result['bumper_game']=$bumperGame;
		$result['normal_game']=$normalGame;
		$result['current_game']=$currentGame;
		$result['upcoming_ticket']=$data;
		$result['user_data']=$userData;

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $result]);
	}


	public function update_payu_payment(Request $request){
		$orderId=$request->order_id;
		$status=$request->status;
		$amount=$request->amount;


		$transaction=Transaction::where('txn_id',$orderId)
							->first();

		if(!$transaction){
			return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
		}

		if($transaction->txn_status!="SUCCESS"){
			$user=User::where('id',$transaction->user_id)
						->first();

			if(strtoupper($status)=="SUCCESS"){
				$transaction->txn_status="SUCCESS";
				$transaction->txn_sub_title="Amount added to wallet successfully";
				$transaction->txn_message="";

				$walletBalanace=$user->wallet_balance;
				$user->wallet_balance=$walletBalanace+$transaction->txn_amount;
				$user->save();

				$transaction->closing_balance=$user->wallet_balance;

			}else{
				$transaction->txn_status="FAILED";
				$transaction->txn_sub_title="Failed to add money";
				$transaction->txn_message="If the amount is debited, contact admin.";

				$transaction->closing_balance=$user->wallet_balance;
			}

			$transaction->save();
		}

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);
	}


	public function get_payu_payload(Request $request){
		$orderId=$request->order_id;

		$transaction=Transaction::where('order_id',$orderId)
							->first();

		if(!$transaction){
			return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
		}

		$user=User::where('id',$transaction->user_id)
					->first();

		if(!$user){
			return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
		}

		$name=$user->user_name;
         $email=$user->email;
         $mobile=$user->phone;
         
         $txnid=$transaction->txn_id;
         $amount=$transaction->txn_amount;
         $productname="Cloths";
         $firstname="";
         $lastname="";

         $nameArr=explode(" ",$name);

         if(count($nameArr)>=2){
             $firstname=$nameArr[0];
             $lastname=$nameArr[1];
         }else if(count($nameArr)==1){
             $firstname=$nameArr[0];
             $lastname="Kumar";
         }else{
             $firstname="Ram";
             $lastname="Kumar";
		 }

        $merchantKey="Fvcr3Z";
        $salt="7o9oo9pRJX7TGYz1kmhgEc5SwAZIzc87";

        $hash_string = $merchantKey."|".$txnid."|".$amount."|".$productname."|".$firstname."|".$email."|||||||||||".$salt;
        $hash = strtolower(hash('sha512', $hash_string));

        $resultData=[];
        $resultData['key']=$merchantKey;
        $resultData['txnid']=$txnid;
        $resultData['productinfo']=$productname;
        $resultData['amount']=$amount;
        $resultData['email']=$email;
        $resultData['firstname']=$firstname;
        $resultData['lastname']=$lastname;
        $resultData['phone']=$mobile;
        $resultData['surl']="http://imaryan.in/payment/response.php";
        $resultData['furl']="http://imaryan.in/payment/response.php";
        $resultData['hash']=$hash;

        return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $resultData]);  
	}

	public function initiate_payu_payment(Request $request){
		$orderId=$request->order_id;
		$userId=$request->user_id;
		$txnAmount=$request->txn_amount;

		$transaction=Transaction::where('order_id',$orderId)
								->first();

		if($transaction){
			//Duplicate transaction, create new order id
			return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
		}

		$user=User::where('id',$userId)
				->first();

		if(!$user){
			return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
		}


		DB::beginTransaction();
		try{

			$transactionCount=Transaction::count()+1;
			$txnId="TXNID".$transactionCount;

			$transactionData=[];
			$transactionData['order_id']=$orderId;
			$transactionData['txn_id']=$txnId;
			$transactionData['user_id']=$userId;
			$transactionData['txn_mode']="PAYU";
			$transactionData['txn_type']="ADD";
			$transactionData['txn_status']="PENDING";
			$transactionData['txn_title']="Add money to wallet";
			$transactionData['txn_sub_title']="Add money to wallet in progress";
			$transactionData['txn_admin_title']="Add money by User - PayU";
			$transactionData['txn_message']="Wait for the transaction status to update. It may take up to 24 hours. Contact support in case you need help.";
			$transactionData['txn_amount']=$txnAmount;

			$walletBalanace=$user->wallet_balance;
			$transactionData['closing_balance']=$walletBalanace;
			$transactionData['account_number']="-";
			$transactionData['reference_id']="-";
			$transactionData['account_name']="-";
			$transactionData['account_ifsc']="-";


			$transaction=Transaction::create($transactionData);

			DB::commit();
			return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $transaction]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status' => 'FAILED', 'code' => 'FC_03', 'data' => null]);
		}

	}


	public function get_payment_setting(){
		$paymentSetting=PaymentSetting::first();

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $paymentSetting]);
	}


	// Create UPI order
	public function create_upi_order(Request $request){
		$orderId=$request->order_id;
		$userId=$request->user_id;
		$txnAmount=$request->txn_amount;
		$txnAccountNumber=$request->account_number;
		$referenceId=$request->reference_id;

		$transactionCount=Transaction::count()+1;
		$txnId="TXNID".$transactionCount;

		$transactionData=[];
		$transactionData['order_id']=$orderId;
		$transactionData['txn_id']=$txnId;
		$transactionData['user_id']=$userId;
		$transactionData['txn_mode']="UPI";
		$transactionData['txn_type']="ADD";
		$transactionData['txn_status']="PENDING";
		$transactionData['txn_title']="Add money to wallet";
		$transactionData['txn_sub_title']="Add money to wallet in progress";
		$transactionData['txn_admin_title']="Add money by User - UPI";
		$transactionData['txn_message']="Wait for the transaction status to update. It may take up to 24 hours. Contact support in case you need help.";
		$transactionData['txn_amount']=$txnAmount;

		$user=User::where('id',$userId)
				->first();

		if(!$user){
			return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
		}

		$walletBalanace=$user->wallet_balance;
		$transactionData['closing_balance']=$walletBalanace;
		$transactionData['account_number']=$txnAccountNumber;
		$transactionData['reference_id']=$referenceId;
		$transactionData['account_name']="-";
		$transactionData['account_ifsc']="-";

		$transaction=Transaction::where('order_id',$orderId)
								->first();

		if($transaction){
			//Duplicate transaction, create new order id
			return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
		}

		$transaction=Transaction::create($transactionData);

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $transaction]);
	}


	//Update UPI Status
	public function update_payment_status(Request $request){
		$id=$request->id;
		$orderId=$request->order_id;
		$status=$request->status;
		$referenceId=$request->reference_id;
		$userId=$request->user_id;

		if($referenceId==null){
			$referenceId="-";
		}

		$transaction=Transaction::where('id',$id)
							->where('order_id',$orderId)
							->first();
		
		if($transaction){
			if($transaction->txn_status=="PENDING"){
				if($status=="SUCCESS"){
					$user=User::where('id',$userId)
							->first();

					$walletBalance=$user->wallet_balance;
					$updatedBalanace=$walletBalance+$transaction->txn_amount;
					
					$user->wallet_balance=$updatedBalanace;
					$user->save();

					$transaction->txn_sub_title="Money added to wallet successfully";
					$transaction->txn_message="";
					$transaction->closing_balance=$updatedBalanace;
					$transaction->reference_id=$referenceId;
					$transaction->txn_status="SUCCESS";

					$transaction->save();

				}else if($status=="FAILED"){

					$transaction->txn_sub_title="Failed to add money to wallet";
					$transaction->txn_message="Transaction has failed due to some reason. In case the amount is deducted, it will be recersed back to wallet in 24 hours.";
					$transaction->reference_id=$referenceId;
					$transaction->txn_status="FAILED";

					$transaction->save();

				}else if($status=="CANCELLED"){
					$transaction->txn_sub_title="Transaction cancelled by user";
					$transaction->txn_message="The transaction doesn't complete as it is cancelled by user";
					$transaction->reference_id=$referenceId;
					$transaction->txn_status="CANCELLED";

					$transaction->save();

				}else if($status=="PENDING"){
					$transaction->reference_id=$referenceId;
					$transaction->save();
					
				}else{
					//Invalid transaction status
					return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
				}

				return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $transaction]);
			}else{
				//Transaction cannot be editing as it is a success or failed or refunded transaction
				return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
			}
		}else{
			//Transaction not found
			return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
		}
	}


	public function submit_payment_request(Request $request){
		$orderId=$request->order_id;
		$userId=$request->user_id;
		$txnAmount=$request->txn_amount;
		$txnAccountNumber=$request->account_number;
		$referenceId=$request->reference_id;
		$type=$request->type;

		$transactionCount=Transaction::count()+1;
		$txnId="TXNID".$transactionCount;

		$transactionData=[];
		$transactionData['order_id']=$orderId;
		$transactionData['txn_id']=$txnId;
		$transactionData['user_id']=$userId;
		$transactionData['txn_mode']=$type;
		$transactionData['txn_type']="ADD";
		$transactionData['txn_status']="PENDING";
		$transactionData['txn_title']="Add money to wallet";
		$transactionData['txn_sub_title']="Add money to wallet in progress";
		$transactionData['txn_admin_title']="Add money by User - ".$type;
		$transactionData['txn_message']="Wait for the transaction status to update. It may take up to 24 hours. Contact support in case you need help.";
		$transactionData['txn_amount']=$txnAmount;

		$user=User::where('id',$userId)
				->first();

		if(!$user){
			return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
		}

		$walletBalanace=$user->wallet_balance;
		$transactionData['closing_balance']=$walletBalanace;
		$transactionData['reference_id']=$referenceId;
		$transactionData['account_number']=$txnAccountNumber;
		if($type=="BANK"){
			$transactionData['account_name']=$request->account_name;
			$transactionData['account_ifsc']=$request->account_ifsc;
		}else{
			$transactionData['account_name']="-";
			$transactionData['account_ifsc']="-";
		}

		$transaction=Transaction::where('order_id',$orderId)
								->first();

		if($transaction){
			//Duplicate transaction, create new order id
			return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
		}

		$transaction=Transaction::create($transactionData);

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $transaction]);
	}

	//Cancel payment request
	public function cancel_payment_request(Request $request){
		$id=$request->id;
		$orderId=$request->order_id;

		$transaction=Transaction::where('id',$id)
								->where('order_id',$orderId)
								->first();

		if($transaction&&$transaction->txn_type=="ADD"&&$transaction->txn_mode=="MANUAL"){
			if($transaction->txn_status=="PENDING"){

					$transaction->txn_title="Add money to wallet";
					$transaction->txn_sub_title="Add money request cancelled by user";
					$transaction->txn_message="";
					$transaction->txn_status="CANCELLED";

					$transaction->save();

					return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $transaction]);
			}else{
				return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
			}
		}else{
			return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
		}
	}


	//submit withdrawal request
	public function submit_withdraw_request(Request $request){
		$orderId=$request->order_id;
		$userId=$request->user_id;
		$txnAmount=$request->txn_amount;
		$txnAccountNumber=$request->account_number;
		$txnAccountName=$request->account_name;
		$txnAccountIfsc=$request->account_ifsc;

		$setting=Setting::first();

		$user=User::where('id',$userId)
				->first();

		if(!$user){
			return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
		}

		$walletBalanace=$user->wallet_balance;

		if($walletBalanace<$txnAmount){
			return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
		}

		if($txnAmount<($setting->min_withdrawal)){
			return response()->json(['status' => 'FAILED', 'code' => 'FC_03', 'data' => null]);
		}

		$transaction=Transaction::where('order_id',$orderId)
								->first();

		if($transaction){
			//Duplicate transaction, create new order id
			return response()->json(['status' => 'FAILED', 'code' => 'FC_04', 'data' => null]);
		}

		$transactionCount=Transaction::count()+1;
		$txnId="TXNID".$transactionCount;

		$transactionData=[];
		$transactionData['order_id']=$orderId;
		$transactionData['txn_id']=$txnId;
		$transactionData['user_id']=$userId;
		$transactionData['txn_mode']="BANK";
		$transactionData['txn_type']="WITHDRAW";
		$transactionData['txn_status']="PENDING";
		$transactionData['txn_title']="Withdraw money to bank";
		$transactionData['txn_sub_title']="Withdrawal request submitted successfully";
		$transactionData['txn_admin_title']="Withdraw money to bank";
		$transactionData['txn_message']="It may take upto 24 hours for approval of withdrawal request";
		$transactionData['txn_amount']=$txnAmount;

		
		$updatedBalanace=$walletBalanace-$txnAmount;
		$user->wallet_balance=$updatedBalanace;
		$user->save();

		$transactionData['closing_balance']=$updatedBalanace;
		$transactionData['account_number']=$txnAccountNumber;
		$transactionData['reference_id']="-";
		$transactionData['account_name']=$txnAccountName;
		$transactionData['account_ifsc']=$txnAccountIfsc;

		$transaction=Transaction::create($transactionData);

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $transaction]);
	}


	//Cancel withdrawal request
	public function cancel_withdrawal_request(Request $request){
		$id=$request->id;
		$orderId=$request->order_id;

		$transaction=Transaction::where('id',$id)
								->where('order_id',$orderId)
								->first();

		if($transaction){
			if($transaction->txn_status=="PENDING"){

				$user=User::where('id',$transaction->user_id)
						->first();

				if(!$user){
					return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
				}

				$walletBalanace=$user->wallet_balance;
				$updatedBalanace=$walletBalanace+$transaction->txn_amount;

				$user->wallet_balance=$updatedBalanace;
				$user->save();

				$transaction->closing_balance=$updatedBalanace;
				$transaction->txn_title="Withdrawal request cancelled";
				$transaction->txn_sub_title="Withdrawal request cancelled by user";
				$transaction->txn_message="";
				$transaction->txn_status="CANCELLED";

				$transaction->save();

				return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $transaction]);
			}else{
				return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
			}
		}else{
			return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
		}
	}

	

	public function test_video_upload(Request $request){
		$path = Storage::putFile('uploadedfile', $request->file('uploadedfile'));

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);

	}

	//1. Send Otp
	public function send_otp(Request $request){
		$phone=$request->phone;

		$user=User::where('phone',$phone)->first();

		if(!$user){
			$userData=[];
			$userData['phone']=$phone;
			$otp=$this->getOtp(4);
			// $otp="1234";
			$userData['otp']=$otp;

			// $this->sendOtp($otp,$phone);

			do {
				$code = $this->getCode(6);
				$checkCode = User::where('referral_code', '=', strtoupper($code))->first();
			} while ($checkCode);
			$userData['referral_code']=strtoupper($code);

			$user=User::create($userData);
		}else{
			$otp=$this->getOtp(4);
			// $otp="1234";
			$user->otp=$otp;
			$this->sendOtp($otp,$phone);
			$user->save();
		}

		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);
	}

	public function sendOtp($otp,$number){
		$otpData=[];
        $otpData['api_id']="APITsXpPNWb29866";
        $otpData['api_password']="maxtambolA1";
        $otpData['sms_type']="OTP";
        $otpData['sms_encoding']="1";
        $otpData['sender']="LUDOPD";
        $otpData['message']=$otp." is your Maxtambola app verification code. Ludopd";
        $otpData['number']='91'.$number;
        $otpData['template_id']=113577;

        $setting=Setting::first();
        $apiKey=$setting->sms_api;

        $data_string = json_encode($otpData);

            $ch = curl_init("http://bulksmsplans.com/api/send_sms");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string)
                    )
            );

            curl_exec($ch) . "\n";
            curl_close($ch);
	}

	//2. Verify Otp
	public function verify_otp(Request $request){
		$phone=$request->phone;
		$otp=$request->otp;
		$token=$request->token;

		$user=User::where('phone',$phone)
					->where('otp',$otp)
					->first();

		if($user){
			$user->token=$token;
			$user->save();
			if($user->user_name==null)
			return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $user]);
			else
			return response()->json(['status' => 'SUCCESS', 'code' => 'SC_02', 'data' => $user]);
		}

		return response()->json(['status' => 'SUCCESS', 'code' => 'FC_01', 'data' => null]);
					
	}

	function getOtp($n)
	{
		$characters = '0123456789';
		$randomString = '';

		for ($i = 0; $i < $n; $i++) {
			$index = rand(0, strlen($characters) - 1);
			$randomString .= $characters[$index];
		}

		return $randomString;
	}

	//3. Register User
	public function register_user(Request $request)
	{
		$userName = $request->user_name;
		$phone = $request->phone;
		$path = null;
		if($request->has('referral_code'))
		$referralCode = $request->referral_code;
		else $referralCode="-";

		$user = User::where('phone', $phone)->first();

		if (!$user||$user->user_name!=null) {
			return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
		}

		DB::beginTransaction();
		try {
			$referralUser = User::where('referral_code', $referralCode)->first();

			if ($request->hasFile('profile')) {
				$image = $request->file('profile');
				$name = time() . '.' . $image->getClientOriginalExtension();
				$destinationPath = public_path('/profile');
				$image->move($destinationPath, $name);

				$path = url('') . '/profile/' . $name;
				$user->user_profile=$path;
			}

			$user->user_name=$userName;
			$user->email=$this->getRandomEmail(10);
			$user->save();

			if ($referralUser) {
				$amt=100;
				$referralData['from_user'] = $referralUser->id;
				$referralData['to_user'] = $user->id;
				$referralData['amount'] = $amt;

				$admin=Admin::first();

				$adminBalance=$admin->wallet_balance;
				$userBalance=$user->wallet_balance;
				$lockedUserBalance=$user->locked_balance;
				$referralUserBalance=$referralUser->wallet_balance;
				$lockedReferralUserBalance=$referralUser->locked_balance;

				UserReferral::create($referralData);

				$transactionCount=Transaction::count()+1;
				$txnId="TXNID".$transactionCount;
				$orderId="ORDERID".$transactionCount;

				$transactionData=[];
				$transactionData['order_id']=$orderId;
				$transactionData['txn_id']=$txnId;
				$transactionData['user_id']=$user->id;
				$transactionData['txn_mode']="WALLET";
				$transactionData['txn_type']="REFERRAL";
				$transactionData['txn_status']="SUCCESS";
				$transactionData['txn_message']="";
				$transactionData['txn_amount']=$amt;
				$transactionData['closing_balance']=$userBalance+$amt;
				$transactionData['created_at']=now();
				$transactionData['updated_at']=now();
				$transactionData['txn_title']="Added for referral";
				$transactionData['txn_admin_title']="Paid for referral";
				$transactionData['txn_sub_title']="Referral bonus for referral code ".$referralUser->referral_code;

				$admin->wallet_balance=$adminBalance-2*$amt;
				$admin->save();

				$transaction=Transaction::create($transactionData);
				$user->wallet_balance=$userBalance+$amt;
				$user->locked_balance=$lockedUserBalance+$amt;
				$user->save();

				$transactionCount=$transactionCount+1;
				$txnId="TXNID".$transactionCount;
				$orderId="ORDERID".$transactionCount;

				$transactionData=[];
				$transactionData['order_id']=$orderId;
				$transactionData['txn_id']=$txnId;
				$transactionData['user_id']=$referralUser->id;
				$transactionData['txn_mode']="WALLET";
				$transactionData['txn_type']="REFERRAL";
				$transactionData['txn_status']="SUCCESS";
				$transactionData['txn_message']="";
				$transactionData['txn_amount']=$amt;
				$transactionData['closing_balance']=$referralUserBalance+$amt;
				$transactionData['created_at']=now();
				$transactionData['updated_at']=now();
				$transactionData['txn_title']="Added for referral";
				$transactionData['txn_admin_title']="Paid for referral";
				$transactionData['txn_sub_title']="Referral bonus for referral code ".$user->referral_code;

				$transaction=Transaction::create($transactionData);
				$referralUser->wallet_balance=$referralUserBalance+$amt;
				$referralUser->locked_balance=$lockedReferralUserBalance+$amt;
				$referralUser->save();

				$setting=Setting::first();
				if($setting->user_signup_notification==1){
					$title="New User Singup";
					$message="New user registered by ".$user->phone;

					$tokenList=[];
					array_push($tokenList,$admin->token);
					$this->notification($tokenList,$title,$message,$setting->gcm_auth);
				}

			}
			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$user]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>null]);
		}
	}


	function getRandomEmail($n){
		$characters = 'abcdefghijklmnopqrstuvwxyz';
		$randomString = '';

		for ($i = 0; $i < $n; $i++) {
			$index = rand(0, strlen($characters) - 1);
			$randomString .= $characters[$index];
		}

		return $randomString."@gmail.com";
	}

	//Get code of n digit
	function getCode($n)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';

		for ($i = 0; $i < $n; $i++) {
			$index = rand(0, strlen($characters) - 1);
			$randomString .= $characters[$index];
		}

		return $randomString;
	}


	//4. Update Token
	public function update_token(Request $request){
		$phone=$request->phone;
		$token=$request->token;

		$user=User::where('phone',$phone)->first();

		if($user){
			$user->token=$token;
			$user->save();

			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$user]);
		}

		return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
	}


	//5. Update Profile
	public function update_profile(Request $request){
		$userName = $request->user_name;
		$phone = $request->phone;
		$path = null;

		$user = User::where('phone', $phone)->first();

		if (!$user) {
			return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
		}

		if ($request->hasFile('profile')) {
			$image = $request->file('profile');
			$name = time() . '.' . $image->getClientOriginalExtension();
			$destinationPath = public_path('/profile');
			$image->move($destinationPath, $name);

			$path = url('') . '/profile/' . $name;
		}

		$user->user_name=$userName;
		if($path!=null){
			$user->user_profile=$path;
		}

		$user->save();
		return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $user]);
	}


	//6. Get My Tickets
	public function get_my_tickets($userId){
		$currentGame=CurrentGame::first();
		$gameDateTime=$currentGame->game_datetime;

	
		// $gameTicketsToday=GameTicket::where('game_date',$gameDate)
		// 						->whereTime('game_time','>=',$gameTime)
		// 						->where('user_id',$userId)
		// 						->orderBy('ticket_number',"ASC");

		$gameTickets=GameTicket::where('game_datetime','>=',$gameDateTime)
								->where('user_id',$userId)
								// ->union($gameTicketsToday)
								->orderBy('ticket_number',"ASC")
								->get()->toArray();

		usort($gameTickets, function($a, $b){
			return strcmp($a['game_date'].' '.$a['game_time'], $b['game_date'].' '.$b['game_time']);
		});
		
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$gameTickets]);
	}

	//7. Get user profile
	public function get_user_profile($userId){
		$user=User::where('id',$userId)->first();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$user]);
	}


	//8. Get next game time
	public function get_next_game_time(){
		$currentGame=CurrentGame::first();

		$now=new Carbon();
		$date=$now->toDateString();
		$time=$now->toTimeString();

		$gameTime=new Carbon($currentGame->game_datetime);

		if($gameTime<$now&&$currentGame->game_status!="WAITING"&&$currentGame->game_status!="STARTED"){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}

		$currentGame['date']=$date;
		$currentGame['time']=$time;
		$currentGame['datetime']=$now->toDateTimeString();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$currentGame]);
	}

	//9. Get prizes
	function get_prizes($type){
		if($type=="BUMPER"){
			$prizes=BumperPrize::where('status',1)->get();
		}else{
			$prizes=Prize::where('status',1)->get();
		}

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$prizes]);
	}

	//10. Get leaderboard (Last Game, Monthly, All Time)
	function get_leaderboard($type){
		//type 0 for last game prizes
		//type 1 for last bumper prizes
		if($type==0){
            $lastGame=GameClaim::leftjoin('played_games','game_claims.game_datetime','played_games.game_datetime')->where('played_games.game_type','NORMAL')->orderBy('game_claims.game_datetime','DESC')->first();
        }else{
            $lastGame=GameClaim::leftjoin('played_games','game_claims.game_datetime','played_games.game_datetime')->where('played_games.game_type','BUMPER')->orderBy('game_claims.game_datetime','DESC')->first();
        }

		if(!$lastGame){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}

		$claims=GameClaim::leftjoin('users','users.id','game_claims.user_id')
						->where('game_claims.game_datetime',$lastGame->game_datetime)
						->select('game_claims.*','users.user_name','users.user_profile')
						->get();
			
		$finalClaims=[];

			foreach($claims as $claim){
				if($claim['prize_tag']=="HALFSHEETBONUS"||$claim['prize_tag']=="FULLSHEETBONUS"){
					$sheetClaims=SheetClaim::where('game_claim_id',$claim['id'])
										->get();
					
					foreach($sheetClaims as $sheetClaim){
						$claimData=[];
						$claimData['game_date']=$claim['game_date'];
						$claimData['game_time']=$claim['game_time'];
						$claimData['prize_name']=$claim['prize_name'];
						$claimData['prize_tag']=$claim['prize_tag'];
						$claimData['prize_amount']=$claim['prize_amount'];
						$claimData['user_id']=$claim['user_id'];
						$claimData['ticket_number']=$sheetClaim['ticket_number'];
						$claimData['ticket']=$sheetClaim['ticket'];
						$claimData['checked_number']=$sheetClaim['checked_number'];
						$claimData['user_name']=$claim['user_name'];
						$claimData['user_profile']=$claim['user_profile'];

						array_push($finalClaims,$claimData);
					}

				}else{
					$claimData=[];
					$claimData['game_date']=$claim['game_date'];
					$claimData['game_time']=$claim['game_time'];
					$claimData['prize_name']=$claim['prize_name'];
					$claimData['prize_tag']=$claim['prize_tag'];
					$claimData['prize_amount']=$claim['prize_amount'];
					$claimData['user_id']=$claim['user_id'];
					$claimData['ticket_number']=$claim['ticket_number'];
					$claimData['ticket']=$claim['ticket'];
					$claimData['checked_number']=$claim['checked_number'];
					$claimData['user_name']=$claim['user_name'];
					$claimData['user_profile']=$claim['user_profile'];
					
					array_push($finalClaims,$claimData);
				}
			}
			
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$finalClaims]);
	}

	//11. Get all tickets
	public function get_all_tickets(){
		$tickets=Ticket::orderBy('ticket_number')
						->get();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$tickets]);
	}

	//12. Purchase tickets
	public function purchase_ticket(Request $request){
		$ticketNumbers=$request->ticket_numbers;
		$userId=$request->user_id;
		$gameDatetime=$request->game_datetime;

		sort($ticketNumbers);
		$admin=Admin::first();

		$user=User::where('id',$userId)->first();
		$customerName=$user->user_name;
		$customerProfile=$user->user_profile;

		$now=new Carbon();

		$game=Game::where('game_datetime',$gameDatetime)
						->first();

		$gameDate=$game->game_date;
		$gameTime=$game->game_time;
		$gameDateTime=$game->game_datetime;
		$ticketPrice=$game->ticket_price;
		$gameType=$game->game_type;

		$bookingCloseTime=new Carbon($game->game_datetime);
		$bookingCloseTime->subMinutes($game->booking_close_minutes);	

		
		$ticketCount=count($ticketNumbers);

		$transactionCount=Transaction::count()+1;
		$txnId="TXNID".$transactionCount;
		$orderId="ORDERID".$transactionCount;

		if($bookingCloseTime<$now){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}

		if($user->wallet_balance<($ticketPrice*$ticketCount)){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>null]);
		}

		DB::beginTransaction();

		try{
			$gameTickets = GameTicket::where('game_date', $gameDate)
			->where('game_time', $gameTime)
			->whereIn('ticket_number', $ticketNumbers)
			->get();

			$adminBalance=$admin->wallet_balance;
			$socketData=[];

			$categoryDetails = $this->sheet_search($ticketNumbers);
			if ($gameTickets->isEmpty()) {
				$ticketDataSheet=[];

				if (count($categoryDetails['random']) > 0) {
					if($gameType=="BUMPER"){
						$tickets = BumperTicket::whereIn('ticket_number', $categoryDetails['random'])
										->get();
					}else{
						$tickets = Ticket::whereIn('ticket_number', $categoryDetails['random'])
										->get();
					}
					
					foreach ($tickets as $ticket) {
							$ticketData = [];
							$ticketData = [];
							$ticketData['game_date'] = $gameDate;
							$ticketData['game_time'] = $gameTime;
							$ticketData['game_datetime']=$gameDateTime;
							$ticketData['ticket_number'] = $ticket->ticket_number;
							$ticketData['ticket'] = $ticket->ticket;
							$ticketData['ticket_price']=$ticketPrice;
							$ticketData['sheet_number'] = -1;
							$ticketData['sheet_type'] = "RANDOM";
							$ticketData['user_id']=$userId;
							$ticketData['transaction_id']=$txnId;
							$ticketData['created_at'] = now();
							$ticketData['updated_at'] = now();

							$ticketSocket=[];
							$ticketSocket['ticket_number']=$ticket->ticket_number;
							$ticketSocket['customer_name']=$customerName;
							$ticketSocket['customer_profile']=$customerProfile;
							$ticketSocket['user_id']=$userId;

							array_push($socketData,$ticketSocket);

						array_push($ticketDataSheet, $ticketData);
					}
				}

				$nextSheetNumber = -1;
				$gameForSheet = GameTicket::where('game_date', $gameDate)
					->where('game_time', $gameTime)
					->orderBy('sheet_number', 'DESC')
					->first();
				if ($gameForSheet) {
					$nextSheetNumber = $gameForSheet->sheet_number + 1;
				} else {
					$nextSheetNumber = 1;
				}

				if (count($categoryDetails['half_sheet']) > 0) {

					foreach ($categoryDetails['half_sheet'] as $hs) {
						if($gameType=="BUMPER"){
							$tickets = BumperTicket::whereIn('ticket_number', $hs)
											->get();
						}else{
							$tickets = Ticket::whereIn('ticket_number', $hs)
											->get();
						}
						
						foreach ($tickets as $ticket) {
							$ticketData = [];
							$ticketData['game_date'] = $gameDate;
							$ticketData['game_time'] = $gameTime;
							$ticketData['game_datetime']=$gameDateTime;
							$ticketData['ticket_number'] = $ticket->ticket_number;
							$ticketData['ticket'] = $ticket->ticket;
							$ticketData['ticket_price']=$ticketPrice;
							$ticketData['sheet_number'] = $nextSheetNumber;
							$ticketData['sheet_type'] = "HALFSHEET";
							$ticketData['user_id']=$userId;
							$ticketData['transaction_id']=$txnId;
							$ticketData['created_at'] = now();
							$ticketData['updated_at'] = now();

							$ticketSocket=[];
							$ticketSocket['ticket_number']=$ticket->ticket_number;
							$ticketSocket['customer_name']=$customerName;
							$ticketSocket['customer_profile']=$customerProfile;
							$ticketSocket['user_id']=$userId;

							array_push($socketData,$ticketSocket);

							array_push($ticketDataSheet, $ticketData);
						}
						$nextSheetNumber++;
					}
				}

				if (count($categoryDetails['full_sheet']) > 0) {
					foreach ($categoryDetails['full_sheet'] as $fs) {
						if($gameType=="BUMPER"){
							$tickets = BumperTicket::whereIn('ticket_number', $fs)
											->get();
						}else{
							$tickets = Ticket::whereIn('ticket_number', $fs)
											->get();
						}
					
						foreach ($tickets as $ticket) {
							$ticketData = [];
							$ticketData['game_date'] = $gameDate;
							$ticketData['game_time'] = $gameTime;
							$ticketData['game_datetime']=$gameDateTime;
							$ticketData['ticket_number'] = $ticket->ticket_number;
							$ticketData['ticket'] = $ticket->ticket;
							$ticketData['ticket_price']=$ticketPrice;
							$ticketData['sheet_number'] = $nextSheetNumber;
							$ticketData['sheet_type'] = "FULLSHEET";
							$ticketData['user_id']=$userId;
							$ticketData['transaction_id']=$txnId;
							$ticketData['created_at'] = now();
							$ticketData['updated_at'] = now();

							$ticketSocket=[];
							$ticketSocket['ticket_number']=$ticket->ticket_number;
							$ticketSocket['customer_name']=$customerName;
							$ticketSocket['customer_profile']=$customerProfile;
							$ticketSocket['user_id']=$userId;

							array_push($socketData,$ticketSocket);

							array_push($ticketDataSheet, $ticketData);
						}
						$nextSheetNumber++;
					}
				}

				$gameTickets = GameTicket::insert($ticketDataSheet);

				// if($gameType=="BUMPER"){
				// 	BumperTicket::whereIn('ticket_number',$ticketNumbers)
				// 			->update(['customer_name'=>$customerName,'customer_profile'=>$customerProfile,'ticket_status'=>'BOOKED']);
				// }else{
				// 	Ticket::whereIn('ticket_number',$ticketNumbers)
				// 			->update(['customer_name'=>$customerName,'customer_profile'=>$customerProfile,'ticket_status'=>'BOOKED']);
				// }

				$user->fresh();

				$userBalance=$user->wallet_balance;
				$updatedBalance=$userBalance-($ticketCount*$ticketPrice);
				$lockedBalance=$user->locked_balance;
				$updatedLockedBalance= $lockedBalance- ($ticketCount*$ticketPrice);

				if($updatedLockedBalance<0){
					$updatedLockedBalance=0;
				}

				if($user->wallet_balance<($ticketPrice*$ticketCount)){
					DB::rollBack();
					return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>null]);
				}

				$transactionData=[];
				$transactionData['order_id']=$orderId;
				$transactionData['txn_id']=$txnId;
				$transactionData['user_id']=$userId;
				$transactionData['txn_mode']="WALLET";
				$transactionData['txn_type']="PURCHASE";
				$transactionData['txn_status']="SUCCESS";
				$transactionData['txn_message']="";
				$transactionData['reference_id']="-";
				$transactionData['account_number']="-";
				$transactionData['account_name']="-";
				$transactionData['account_ifsc']="-";
				$transactionData['txn_amount']=$ticketCount*$ticketPrice;
				$transactionData['closing_balance']=$updatedBalance;
				$transactionData['created_at']=now();
				$transactionData['updated_at']=now();
				
				if($gameType=="BUMPER"){
					$transactionData['txn_title']="Bumper Ticket Purchase";
					$transactionData['txn_admin_title']="Bumper Ticket Sold";
				}else{
					$transactionData['txn_title']="Ticket Purchase";
					$transactionData['txn_admin_title']="Ticket Sold";
				}
				if($ticketCount==1)
					$transactionData['txn_sub_title']=$ticketCount." Ticket Purchased Successfully";
					else $transactionData['txn_sub_title']=$ticketCount." Tickets Purchased Successfully";

				$admin->wallet_balance=$adminBalance+($ticketPrice*$ticketCount);
				$admin->save();

				$transaction=Transaction::create($transactionData);
				$user->wallet_balance=$updatedBalance;
				$user->locked_balance=$updatedLockedBalance;
				$user->save();

				DB::commit();

				$setting=Setting::first();
				if($setting->ticket_notification==1){
					$title="New Ticket Purchase";
					$message=$ticketCount." Tickets purchased by ".$user->user_name;

					$tokenList=[];
					array_push($tokenList,$admin->token);
					$this->notification($tokenList,$title,$message,$setting->gcm_auth);
				}

				$socketResponse=[];
				$socketResponse['tickets']=$socketData;
				$socketResponse['game_datetime']=$gameDateTime;
				$socketResponse['game_type']=$gameType;

				$data_string = json_encode(['data'=>$socketResponse]);


				if($gameType=="BUMPER"){
					$ch = curl_init('https://www.maxtambola.com:8080/sendBumperSale');
				}else{
					$ch = curl_init('https://www.maxtambola.com:8080/sendTicketSale');
				}
			
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt(
					$ch,
					CURLOPT_HTTPHEADER,
					array(
						'Content-Type: application/json',
						'Content-Length: ' . strlen($data_string)
					)
				);

				echo curl_exec($ch) . "\n";
				curl_close($ch);

				$balanceData=[];
				$balanceData['user_id']=$user->id;
				$balanceData['wallet_balance']=$user->wallet_balance;
				$balanceData['locked_balance']=$user->locked_balance;
			
				$this->sendWalletBalance($balanceData);

				return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
			}else{
				return response()->json(['status'=>'SUCCESS','code'=>'FC_03','data'=>null]);
			}
		}catch(Exception $e){
			DB::rollback();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_04','data'=>$e->getMessage()]);
		}
	}


	//13. Add Transaction
	public function add_transaction(Request $request){
		
		DB::beginTransaction();

		try{
			$user=User::where('id',$request->user_id)->first();
			$admin=Admin::first();

			$userBalance=$user->wallet_balance;
			$adminBalance=$admin->wallet_balance;
			$orderAmount=$request['txn_amount'];

			if($request->txn_type=="WITHDRAW"){

				if($user->wallet_balance<$request['txn_amount']){
					DB::rollBack();
					return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
				}

				$request['closing_balance']=$userBalance-$request['txn_amount'];
				$user->wallet_balance=$userBalance-$request['txn_amount'];
				$admin->wallet_balance=$adminBalance-$request['txn_amount'];
				$user->save();
				$admin->save();

				$transaction=Transaction::create($request->all());

				$transaction->fresh();
				$payoutData=[];
				$payoutData['txn_id']=$transaction->id;
				$payoutData['user_id']=$request->user_id;
				$payoutData['account_name']=$request->account_name;
				$payoutData['account_number']=$request->account_number;
				$payoutData['account_ifsc']=$request->account_ifsc;

	
				$transactionPayout=TransactionPayout::create($payoutData);
			}else{
				$transaction=Transaction::create($request->all());

				$transaction->fresh();
			}

			$setting=Setting::first();
				if($transaction->txn_type=="ADD"){
					$title="Money added by Admin.";
					$message="A amount of Rs ".$orderAmount.' is added by Admin';
				}else if($transaction->txn_status=="WITHDRAW"){
					$title="Amount withdrawn by admin";
					$message="A amount of Rs ".$orderAmount.' is withdrawn by Admin';
				}
				

				$tokenList=[];
				array_push($tokenList,$user->token);
				$this->notification($tokenList,$title,$message,$setting->gcm_auth);

			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>$e->getMessage()]);
		}
	}


	//14. Get Bumper Ticket
	public function get_bumper_ticket(){
		$bumperTickets=BumperTicket::orderBy('ticket_number')
								->get();
								
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$bumperTickets]);
	}

	//15. Get all transactions
	public function get_my_transactions(Request $request){
		$userId=$request->user_id;
		$from_id=$request->from_id;

		if($from_id==0){
			$transactions=Transaction::where('user_id',$userId)
			->orderBy('created_at','DESC')
			->limit(20)
			->get();
		}else{
			$transactions=Transaction::where('user_id',$userId)
			->where('id','<',$from_id)
			->orderBy('created_at','DESC')
			->limit(20)
			->get();
		}
		
								
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transactions]);
	}

	//16. Get Game board data
	public function get_game_data($userId){
		$currentGame=CurrentGame::first();

		$gameDate=$currentGame->game_date;
		$gameTime=$currentGame->game_time;

		$playedGame=PlayedGame::where('game_date',$gameDate)
							->where('game_time',$gameTime)
							->first();
		if(!$playedGame){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}
		$gameJoins=GameJoin::where('played_game_id',$playedGame->id)
							->get();
		if($gameJoins){
			$playerCount=count($gameJoins);
		}else{
			$playerCount=0;
		}
		$ticketCount=0;
		foreach($gameJoins as $gj){
			$ticketCount+=$gj->total_tickets;
		}
		
		
		$claims=GameClaim::leftjoin('users','users.id','game_claims.user_id')
						->where('game_claims.game_date',$gameDate)
						->where('game_claims.game_time',$gameTime)
						->select('game_claims.*','users.user_name','users.user_profile')
						->get();

		if($currentGame->bumper==1){
			$prizes=BumperPrize::where('status',1)
					->get();
			$claimCompleted=BumperPrize::where('prize_status','CLAIMED')
					->pluck('prize_tag');
		}else{
			$prizes=Prize::where('status',1)
						->get();
			$claimCompleted=Prize::where('prize_status','CLAIMED')
						->pluck('prize_tag');
		}
		

		$tickets=GameTicket::where('user_id',$userId)
							->where('game_date',$gameDate)
							->where('game_time',$gameTime)
							->get();
		
		$data=[];
		$data['played_game']=$playedGame;
		$data['prizes']=$prizes;
		$data['claims']=$claims;
		$data['claimed']=$claimCompleted;
		$data['player_count']=$playerCount;
		$data['total_tickets']=$ticketCount;
		$data['tickets']=$tickets;

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$data]);
	}

	//17. Join Game
	public function join_game($userId){
		$currentGame=CurrentGame::first();

		$gameDate=$currentGame->game_date;
		$gameTime=$currentGame->game_time;

		$gameTickets=GameTicket::where('game_date',$gameDate)
								->where('game_time',$gameTime)
								->where('user_id',$userId)
								->count();
		if($gameTickets==0){
			Log::channel('testlog')->info("FC_01 ".$userId." ".$gameDate." ".$gameTime);
			return response()->json(['status'=>'FAILED','code'=>'FC_01','data'=>null]);
		}

		$playedGame=PlayedGame::where('game_date',$gameDate)
							->where('game_time',$gameTime)
							->first();

		if(($currentGame->game_status=="WAITING"||$currentGame->game_status=="STARTED")&&$playedGame){

			$gameJoin=GameJoin::where('played_game_id',$playedGame->id)
							->where('user_id',$userId)
							->first();

			if(!$gameJoin){
				$gameJoinData=[];
				$gameJoinData['played_game_id']=$playedGame->id;
				$gameJoinData['user_id']=$userId;
				$gameJoinData['total_tickets']=$gameTickets;

				$gameJoin=GameJoin::create($gameJoinData);

				$socketData=[];
				$socketData['ticket_count']=$gameTickets;
				$socketData['player_increment']=1;


				$data_string = json_encode($socketData);

				$ch = curl_init('https://www.maxtambola.com:8080/sendJoinData');
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt(
					$ch,
					CURLOPT_HTTPHEADER,
					array(
						'Content-Type: application/json',
						'Content-Length: ' . strlen($data_string)
					)
				);

				echo curl_exec($ch) . "\n";
				curl_close($ch);

			}

			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$gameJoin]);
		}

		Log::channel('testlog')->info("FC_02 ".$userId." ".$gameDate." ".$gameTime." ".$currentGame->game_status);
		return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>null]);
	}


	//18. Claim Prize
	public function claim_prize(Request $request){
		$prizeTag=$request->prize_tag;
		$ticketData=$request->ticket_data;
		$userId=$request->user_id;


		$currentGame=CurrentGame::first();
		$gameDate=$currentGame->game_date;
		$gameTime=$currentGame->game_time;
		$selectedTicketData=$ticketData[0];

		Log::channel('testlog')->info("Claim Data ".$userId." ".$prizeTag." ".$currentGame->game_datetime." ".json_encode($ticketData));


		if(count($ticketData)==0||!$selectedTicketData){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}

		usort($ticketData, function($a, $b)
        {
              if($a['ticket_number']<$b['ticket_number']){
              return -1;
              }else if($a['ticket_number']>$b['ticket_number']){
              return 1;
              }else{
              return 0;
              }
        });
		

		$fullsheetNumbers=[];
		$firstHalfSheetNumbers=[];

		if($prizeTag=="HALFSHEETBONUS"){
			if($selectedTicketData['sheet_type']!="HALFSHEET"&&$selectedTicketData['sheet_type']!="FULLSHEET"){
				return response()->json(['status'=>'SUCCESS','code'=>'FC_05','data'=>null]);
			}

			$selectedSheetType=$selectedTicketData['sheet_type'];
			$selectedSheetNumber=$selectedTicketData['sheet_number'];

			if($selectedSheetType=="FULLSHEET"){
				$diff=$selectedTicketData['ticket_number']-$ticketData[0]['ticket_number'];
				$firstPart=true;
				if(floor($diff/3)==0){
					$firstPart=true;
				}else{
					$firstPart=false;
				}

				if(count($ticketData)==6){
					for($i=0;$i<6;$i++){
						if($firstPart){
							if($i<3){
								array_push($firstHalfSheetNumbers,$ticketData[$i]['ticket_number']);
							}	
						}else{
							if($i>=3){
								array_push($firstHalfSheetNumbers,$ticketData[$i]['ticket_number']);
							}
						}
					}
				}
			}else{
				for($i=0;$i<count($ticketData);$i++){
					if($ticketData[$i]['sheet_type']=="HALFSHEET"&&$ticketData[$i]['sheet_number']==$selectedSheetNumber){
						array_push($firstHalfSheetNumbers,$ticketData[$i]['ticket_number']);
					}
				}
			}

		}else if($prizeTag=="FULLSHEETBONUS"){
			if($selectedTicketData['sheet_type']!="FULLSHEET"){
				return response()->json(['status'=>'SUCCESS','code'=>'FC_05','data'=>null]);
			}
			if(count($ticketData)==6){
				for($i=0;$i<6;$i++){
					if($ticketData[$i]['sheet_type']=="FULLSHEET"){
						array_push($fullsheetNumbers,$ticketData[$i]['ticket_number']);
					}
				}
			}
		}

		DB::beginTransaction();

		try{
			if($currentGame->bumper==1){
				$prize=BumperPrize::where('prize_tag',$prizeTag)
							->whereIn('prize_status',['NEW','CLAIMING'])
							->first();
			}else{
				$prize=Prize::where('prize_tag',$prizeTag)
							->whereIn('prize_status',['NEW','CLAIMING'])
							->first();
			}
	
			
			if(!$prize){
				DB::rollBack();
				return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>null]);
			}
	
			$claimedPrize=GameClaim::where('game_date',$gameDate)
								->where('game_time',$gameTime)
								->where('prize_tag',$prizeTag)
								->where('ticket_number',$selectedTicketData['ticket_number'])
								->first();
			if($claimedPrize&&$selectedTicketData['sheet_type']!="FULLSHEET"&&$selectedTicketData['sheet_type']!="HALFSHEET"){
				DB::rollBack();
				return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>null]);
			}

			$playedGame=PlayedGame::where('game_date',$gameDate)
							->where('game_time',$gameTime)
							->first();

			$calledNumbersArray=explode(',',substr($playedGame->called_numbers,0,-1));
			$ticketNumber=$selectedTicketData['ticket_number'];
			$selectedNumberArray=explode(',',substr($selectedTicketData['selected_numbers'],0,-1));

			$gameTicket=GameTicket::where('game_date',$gameDate)
							->where('game_time',$gameTime)
							->where('ticket_number',$selectedTicketData['ticket_number'])
							->first();

			$ticketCombo=explode(',',substr($gameTicket->ticket,0,-1));

			$topLine = array_slice($ticketCombo, 0, 9);
			$middleLine = array_slice($ticketCombo, 9, 9);
			$bottomLine = array_slice($ticketCombo, 18, 9);
			$topLineNum=[];
			$middleLineNum=[];
			$bottomLineNum=[];
			$corners=[];
			$fullHouse=[];

			$i=0;
			$j=0;
			foreach($topLine as $item){
				if($item!=" "){
					if($i==0||$i==4){
						array_push($corners, $item);
					}
					array_push($fullHouse, $item);
					array_push($topLineNum, $item);
					$i++;
				}
				$j++;
			}

			$i=0;
			$j=0;
			foreach($middleLine as $item){
				if($item!=" "){
					array_push($fullHouse, $item);
					array_push($middleLineNum, $item);
					$i++;
				}
				$j++;
			}
			
			$i=0;
			$j=0;
			foreach($bottomLine as $item){
				if($item!=" "){
					if($i==0||$i==4){
						array_push($corners, $item);
					}
					array_push($fullHouse, $item);
					array_push($bottomLineNum, $item);
					$i++;
				}
				$j++;
			}

			$claimData=[];
			$claimData['game_date']=$gameDate;
			$claimData['game_time']=$gameTime;
			$claimData['game_datetime']=$currentGame->game_datetime;
			$claimData['prize_name']=$prize->prize_name;
			$claimData['prize_tag']=$prizeTag;
			$claimData['prize_amount']=0;
			$claimData['user_id']=$userId;
			$claimData['ticket_number']=$ticketNumber;
			$claimData['ticket']=$gameTicket->ticket;
			$claimData['checked_number']=$selectedTicketData['selected_numbers'];
			$claimData['created_at']=now();
			$claimData['updated_at']=now();

			$prizeClaimed=false;


			if($prizeTag=="EARLY5"){
				$diff=array_intersect($calledNumbersArray,$selectedNumberArray);

				if(count($diff)>=5){
					$claimedPrize=GameClaim::create($claimData);

					$prizeClaimed=true;
				}

			}else if($prizeTag=="CORNERS"){
				$callCornersDiff=array_intersect($calledNumbersArray,$corners);
				$selectedCornersDiff=array_intersect($selectedNumberArray,$corners);

				if(count($callCornersDiff)==count($corners)&&count($selectedCornersDiff)==count($corners)){
					$claimedPrize=GameClaim::create($claimData);
					$prizeClaimed=true;
				}

			}else if($prizeTag=="TOPLINE"){
				$callTopLineDiff=array_intersect($calledNumbersArray,$topLineNum);
				$selectedTopLineDiff=array_intersect($selectedNumberArray,$topLineNum);

				if(count($callTopLineDiff)==count($topLineNum)&&count($selectedTopLineDiff)==count($topLineNum)){
					$claimedPrize=GameClaim::create($claimData);
					$prizeClaimed=true;
				}

			}else if($prizeTag=="MIDDLELINE"){
				$callMiddleLineDiff=array_intersect($calledNumbersArray,$middleLineNum);
				$selectedMiddleLineDiff=array_intersect($selectedNumberArray,$middleLineNum);

				if(count($callMiddleLineDiff)==count($middleLineNum)&&count($selectedMiddleLineDiff)==count($middleLineNum)){
					$claimedPrize=GameClaim::create($claimData);
					$prizeClaimed=true;
				}
			}else if($prizeTag=="BOTTOMLINE"){
				$callBottomLineDiff=array_intersect($calledNumbersArray,$bottomLineNum);
				$selectedBottomLineDiff=array_intersect($selectedNumberArray,$bottomLineNum);

				if(count($callBottomLineDiff)==count($bottomLineNum)&&count($selectedBottomLineDiff)==count($bottomLineNum)){
					$claimedPrize=GameClaim::create($claimData);
					$prizeClaimed=true;
				}
			}else if($prizeTag=="FULLHOUSE"){
				$callFullHouseDiff=array_intersect($calledNumbersArray,$fullHouse);
				$selectedFullHouseDiff=array_intersect($selectedNumberArray,$fullHouse);

				if(count($callFullHouseDiff)==count($fullHouse)&&count($selectedFullHouseDiff)==count($fullHouse)){
					$claimedPrize=GameClaim::create($claimData);
					$prizeClaimed=true;
				}
			}else if($prizeTag=="FULLHOUSE2"){
				if($currentGame->bumper==1){
					$prizeNew=BumperPrize::where('prize_tag','FULLHOUSE')
							->where('prize_status','CLAIMED')
							->first();
				}else{
					$prizeNew=Prize::where('prize_tag','FULLHOUSE')
							->where('prize_status','CLAIMED')
							->first();
				}

				$claimedPrize=GameClaim::where('game_date',$gameDate)
							->where('game_time',$gameTime)
							->where('prize_tag','FULLHOUSE')
							->where('ticket_number',$selectedTicketData['ticket_number'])
							->first();

				if($prizeNew&&!$claimedPrize){
					$callFullHouseDiff=array_intersect($calledNumbersArray,$fullHouse);
					$selectedFullHouseDiff=array_intersect($selectedNumberArray,$fullHouse);
	
					if(count($callFullHouseDiff)==count($fullHouse)&&count($selectedFullHouseDiff)==count($fullHouse)){
						$claimedPrize=GameClaim::create($claimData);
						$prizeClaimed=true;
					}
				}

			}else if($prizeTag=="FULLHOUSE3"){
				if($currentGame->bumper==1){
					$prizeNew=BumperPrize::whereIn('prize_tag',['FULLHOUSE','FULLHOUSE2'])
							->where('prize_status','CLAIMED')
							->first();
				}else{
					$prizeNew=Prize::whereIn('prize_tag',['FULLHOUSE','FULLHOUSE2'])
							->where('prize_status','CLAIMED')
							->first();
				}

				$claimedPrize=GameClaim::where('game_date',$gameDate)
							->where('game_time',$gameTime)
							->whereIn('prize_tag',['FULLHOUSE','FULLHOUSE2'])
							->where('ticket_number',$selectedTicketData['ticket_number'])
							->first();

				if($prizeNew&&!$claimedPrize){
					$callFullHouseDiff=array_intersect($calledNumbersArray,$fullHouse);
					$selectedFullHouseDiff=array_intersect($selectedNumberArray,$fullHouse);
	
					if(count($callFullHouseDiff)==count($fullHouse)&&count($selectedFullHouseDiff)==count($fullHouse)){
						$claimedPrize=GameClaim::create($claimData);
						$prizeClaimed=true;
					}
				}
			}else if($prizeTag=="HALFSHEETBONUS"){
				$claimedPrize=SheetClaim::where('game_date',$gameDate)
							->where('game_time',$gameTime)
							->where('prize_tag',$prizeTag)
							->where('user_id',$userId)
							->where('ticket_number',$ticketNumber)
							->first();
				if($claimedPrize){
					return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>null]);
				}

				$countNumbers=[];
				if(count($firstHalfSheetNumbers)==3){
					foreach($ticketData as $tktData){
						if(in_array($tktData['ticket_number'],$firstHalfSheetNumbers)){
							$selectedNumberArray=explode(',',$tktData['selected_numbers']);
							$callNumDiff=array_intersect($calledNumbersArray,$selectedNumberArray);
							array_push($countNumbers,count($callNumDiff));
						}
					}

					$minCount=min($countNumbers);
					if($minCount>=2){
						$claimedPrize=GameClaim::create($claimData);
						$prizeClaimed=true;

						foreach($ticketData as $tktData){
							if(in_array($tktData['ticket_number'],$firstHalfSheetNumbers)){
								$gameTicket=GameTicket::where('game_date',$gameDate)
							->where('game_time',$gameTime)
							->where('ticket_number',$tktData['ticket_number'])
							->first();
								$sheetClaimData=[];
								$sheetClaimData['game_claim_id']=$claimedPrize->id;
								$sheetClaimData['ticket_number']=$tktData['ticket_number'];
								$sheetClaimData['ticket']=$gameTicket['ticket'];
								$sheetClaimData['user_id']=$userId;
								$sheetClaimData['game_date']=$gameDate;
								$sheetClaimData['game_time']=$gameTime;
								$sheetClaimData['checked_number']=$tktData['selected_numbers'];
								$sheetClaimData['prize_tag']=$prizeTag;
								$sheetClaimData['sheet_type']=$tktData['sheet_type'];
								$sheetClaimData['created_at']=now();
								$sheetClaimData['updated_at']=now();

								$sheetClaim=SheetClaim::create($sheetClaimData);
							}
						}
					}
				}


			}else if($prizeTag=="FULLSHEETBONUS"){

				$claimedPrize=SheetClaim::where('game_date',$gameDate)
							->where('game_time',$gameTime)
							->where('prize_tag',$prizeTag)
							->where('user_id',$userId)
							->where('ticket_number',$ticketNumber)
							->where('sheet_type',"FULLSHEET")
							->first();
				if($claimedPrize){
					return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>null]);
				}

				$countNumbers=[];
				if(count($fullsheetNumbers)==6){
					foreach($ticketData as $tktData){
						$selectedNumberArray=explode(',',$tktData['selected_numbers']);
						$callNumDiff=array_intersect($calledNumbersArray,$selectedNumberArray);
						array_push($countNumbers,count($callNumDiff));
					}

					$minCount=min($countNumbers);
					if($minCount>=2){
						$claimedPrize=GameClaim::create($claimData);
						$prizeClaimed=true;

						foreach($ticketData as $tktData){
							$gameTicket=GameTicket::where('game_date',$gameDate)
							->where('game_time',$gameTime)
							->where('ticket_number',$tktData['ticket_number'])
							->first();
							$sheetClaimData=[];
							$sheetClaimData['game_claim_id']=$claimedPrize->id;
							$sheetClaimData['ticket_number']=$tktData['ticket_number'];
							$sheetClaimData['ticket']=$gameTicket['ticket'];
							$sheetClaimData['user_id']=$userId;
							$sheetClaimData['game_date']=$gameDate;
							$sheetClaimData['game_time']=$gameTime;
							$sheetClaimData['checked_number']=$tktData['selected_numbers'];
							$sheetClaimData['prize_tag']=$prizeTag;
							$sheetClaimData['sheet_type']=$tktData['sheet_type'];
							$sheetClaimData['created_at']=now();
							$sheetClaimData['updated_at']=now();

							$sheetClaim=SheetClaim::create($sheetClaimData);
						}
					}
				}
			}


		$user=User::where('id',$userId)->first();

		$claimData['user_name']=$user->user_name;
		$claimData['user_profile']=$user->user_profile;

		$setting=Setting::first();
		$admin=Admin::where('id',1)->first();
		if($setting->prize_cliam_notification==1){
			$title=$prize->prize_name."Claimed";
			$message=$prize->prize_name." claimed by ".$user->user_name;

			$tokenList=[];
			array_push($tokenList,$admin->token);
			$this->notification($tokenList,$title,$message,$setting->gcm_auth);
		}


		if($prizeClaimed){
			//Send data to socket

			if($prize->prize_status=="NEW"){
				$prize->prize_status="CLAIMING";
				$prize->save();
			}

			DB::commit();

			$data_string = json_encode($claimData);

			$ch = curl_init('https://www.maxtambola.com:8080/sendPrizeClaim');
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen($data_string)
				)
			);

			curl_exec($ch) . "\n";
			curl_close($ch);


			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$claimedPrize]);
		}else{
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_03','data'=>$claimedPrize]);
		}
		
		}catch(Exception $e){
			DB::rollback();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_04','data'=>null]);
		}

		
	}


	//Search Sheet
	public function sheet_search($test)
	{
		$data = [];
		$j = 0;
		$i = 0;
		$start = $test[0];
		$z = 0;
		while ($z < count($test)) {
			if ($test[$z] - $start == $i) {
				if (!array_key_exists($j, $data)) {
					$data[$j] = [];
				}
				array_push($data[$j], $test[$z]);
				$z++;
				$i++;
			} else {
				$j++;
				$i = 0;
				$start = $test[$z];
			}
		}


		$newData = [];
		$newData['random'] = [];
		$newData['half_sheet'] = [];
		$newData['full_sheet'] = [];
		$fullSheetIndex = 0;

		// foreach($data as $d){
		// 	if(count($d)<3){
		// 		$newData['random']=array_merge($newData['random'],$d);
		// 	}else if(count($d)==3){
		// 		array_push($newData['half_sheet'],$d);
		// 	}else if(count($d)>3&&count($d)<6){
		// 		array_push($newData['half_sheet'],array_slice($d,0,3));
		// 		if(count($d)>3){
		// 			$newData['random']=array_merge($newData['random'],array_slice($d,3,count($d)-3));
		// 		}
		// 	}else if(count($d)==6){
		// 		array_push($newData['full_sheet'],$d);
		// 	}else{
		// 		$size=count($d);
		// 		$fullSheetCount=intdiv($size,6);
		// 		$remaining=$size-($fullSheetCount*6);
		// 		$halfCount=intdiv($remaining,3);
		// 		$remaining2=$size-(($fullSheetCount*6)+($halfCount*3));

		// 		if($fullSheetCount!=0){
		// 			$onlySix=array_slice($d,0,(6*$fullSheetCount));
		// 			$chunks = array_chunk($onlySix, 6, false);

		// 			for ($t = 0; $t < count($chunks); $t++) {
		// 				if (!array_key_exists($fullSheetIndex, $newData['full_sheet'])) {
		// 					$newData['full_sheet'][$fullSheetIndex] = [];
		// 				}
		// 				$newData['full_sheet'][$fullSheetIndex] = array_merge($newData['full_sheet'][$fullSheetIndex], $chunks[$t]);
		// 				$fullSheetIndex++;
		// 			}
		// 		}

		// 		if($halfCount!=0){
		// 			array_push($newData['half_sheet'],array_slice($d,(6*$fullSheetCount),$size-(6*$fullSheetCount)-1));
		// 		}

		// 		if($remaining2!=0){
		// 			$newData['random']=array_merge($newData['random'],array_slice($d,(6*$fullSheetCount)+(3*$halfCount),$remaining2));
		// 		}
		// 	}
		// }

		foreach ($data as $d) {
			if (count($d) < 3) {
				$newData['random'] = array_merge($newData['random'], $d);
			} else if (count($d) < 6) {
				$firstNum = $d[0];
				foreach ($d as $ad) {
					if ($ad % 3 == 1) {
						$firstNum = $ad;
						break;
					}
				}
				$box3 = [];
				array_push($box3, $firstNum);
				array_push($box3, $firstNum + 1);
				array_push($box3, $firstNum + 2);
				$diff = array_diff($d, $box3);
				$newData['random'] = array_merge($newData['random'], $diff);
				$inter = array_intersect($d, $box3);
				// return $d[2];
				if (count($inter) == 3) {
					array_push($newData['half_sheet'], $inter);
				} else {
					$newData['random'] = array_merge($newData['random'], $inter);
				}
			} else {
				$firstHalf = [];
				$lastHalf = [];

				$dcopy = $d;


				foreach ($dcopy as $z) {
					if ($z % 6 == 1) {
						break;
					}
					array_push($firstHalf, $z);
				}

				$dcopy = array_reverse($dcopy);

				foreach ($dcopy as $z) {
					if ($z % 6 == 0) {
						break;
					}
					array_push($lastHalf, $z);
				}

				// return $lastHalf;

				// array_push($data, $firstHalf);
				// array_push($data, $lastHalf);
				$dcopy = array_reverse($dcopy);
				$lastHalf = array_reverse($lastHalf);

				$onlySix = array_diff($dcopy, $firstHalf, $lastHalf);

				$chunks = array_chunk($onlySix, 6, false);

				for ($t = 0; $t < count($chunks); $t++) {
					if (!array_key_exists($fullSheetIndex, $newData['full_sheet'])) {
						$newData['full_sheet'][$fullSheetIndex] = [];
					}
					$newData['full_sheet'][$fullSheetIndex] = array_merge($newData['full_sheet'][$fullSheetIndex], $chunks[$t]);
					$fullSheetIndex++;
				}




				$xData = [];
				array_push($xData, $firstHalf);
				array_push($xData, $lastHalf);

				foreach ($xData as $x) {
					if (count($x) < 3) {
						$newData['random'] = array_merge($newData['random'], $x);
					} else if (count($x) < 6) {
						$firstNum = $x[0];
						foreach ($x as $xd) {
							if ($xd % 3 == 1) {
								$firstNum = $xd;
								break;
							}
						}
						$box3 = [];
						array_push($box3, $firstNum);
						array_push($box3, $firstNum + 1);
						array_push($box3, $firstNum + 2);
						$diff = array_diff($x, $box3);
						$newData['random'] = array_merge($newData['random'], $diff);
						$inter = array_intersect($x, $box3);
						// return $d[2];
						if (count($inter) == 3) {
							array_push($newData['half_sheet'], $inter);
						} else {
							$newData['random'] = array_merge($newData['random'], $inter);
						}
					}
				}
				// return $newData['full_sheet'];
			}
		}
		return $newData;
	}


	//19. Get Bumper Game Details
	public function get_bumper_game(){
		$now=new Carbon();
		$bumperGame=Game::where('game_datetime','>=',$now)
						->where('type','BUMPER')
						->orderBy('game_datetime','DESC')
						->first();
		
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$bumperGame]);
	}


	//20. Update Transaction Status
	public function update_transaction(Request $request){
		$orderId=$request->order_id;
		$status=$request->txn_status;

		$transaction=Transaction::where('order_id',$orderId)
								->first();
		$admin=Admin::first();
		
		DB::beginTransaction();

		try{
			$user=User::where('id',$transaction->user_id)
						->first();
			$userBalance=$user->wallet_balance;
			$adminBalance=$admin->wallet_balance;

			if($transaction->txn_status=="PENDING"){
				$transaction->txn_status=$status;
				if($transaction->txn_type=="ADD"){
					if($status=="SUCCESS"){
						$updatedBalance=$userBalance+$transaction->txn_amount;
						$user->wallet_balance=$updatedBalance;
						$admin->wallet_balance=$adminBalance+$transaction->txn_amount;
						$transaction->closing_balance=$updatedBalance;
						$transaction->txn_title="Add Money to wallet";
						$transaction->txn_sub_title="Money added successfully";
						$transaction->txn_admin_title="Add Money by user";
						$transaction->txn_message="";
						$transaction->save();
						$user->save();
						$admin->save();

					}else if($status=="FAILED"){
						$transaction->txn_title="Add Money to wallet";
						$transaction->txn_sub_title="Failed to add money";
						$transaction->txn_admin_title="Add Money by user";
						$transaction->txn_message="If the amount is deducted from your account, the amount will be refunded back to your account in 7-10 working days.";
						$transaction->save();
					}
				}else{
					if($status=="SUCCESS"){
						// $updatedBalance=$userBalance-$transaction->txn_amount;
						// $user->wallet_balance=$updatedBalance;
						// $transaction->closing_balance=$updatedBalance;
						$transaction->txn_title="Withdraw Money from wallet";
						$transaction->txn_sub_title="Money withdrawn successfully";
						$transaction->txn_admin_title="Withdrawn by user";
						$transaction->txn_message="";
						// $user->save();
						$transaction->save();

					}else if($status=="FAILED"){
						$updatedBalance=$userBalance+$transaction->txn_amount;
						$user->wallet_balance=$updatedBalance;
						$admin->wallet_balance=$adminBalance+$transaction->txn_amount;
						$transaction->closing_balance=$updatedBalance;
						$transaction->txn_title="Withdraw Money from wallet";
						$transaction->txn_sub_title="Failed to withdraw money";
						$transaction->txn_admin_title="Withdrawn by user";
						$transaction->txn_message="";
						$user->save();
						$admin->save();
						$transaction->save();
					}
				}
			}
			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}
	}

	//21. Save Bank
	public function save_bank(Request $request){
		$userBank=UserBank::where('user_id',$request->user_id)
					->first();
		
		if(!$userBank){
			$userBank=UserBank::create($request->all());
		}else{
			$userBank->account_name=$request->account_name;
			$userBank->account_number=$request->account_number;
			$userBank->account_ifsc=$request->account_ifsc;
			$userBank->save();
		}

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$userBank]);
	}

	//22. Get Bank
	public function get_bank($userId){
		$userBank=UserBank::where('user_id',$userId)
						->first();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$userBank]);
	}

	//23. Submit KYC Request
	public function submit_kyc_request(Request $request){
		$userId=$request->user_id;

		$kycUser=UserKyc::where('user_id',$userId)->first();
		$panFront="";$panBack="";$profile="";

		if ($request->hasFile('pan_front')) {
			$image = $request->file('pan_front');
			$name = time() . '.' . $image->getClientOriginalExtension();
			$destinationPath = public_path('/pan');
			$image->move($destinationPath, $name);

			$panFront = url('') . '/pan/' . $name;
		}
		if ($request->hasFile('pan_back')) {
			$image = $request->file('pan_back');
			$name = time()+1 . '.' . $image->getClientOriginalExtension();
			$destinationPath = public_path('/pan');
			$image->move($destinationPath, $name);

			$panBack = url('') . '/pan/' . $name;
		}
		if ($request->hasFile('profile')) {
			$image = $request->file('profile');
			$name = time() . '.' . $image->getClientOriginalExtension();
			$destinationPath = public_path('/profile');
			$image->move($destinationPath, $name);

			$profile = url('') . '/profile/' . $name;
		}

		if($kycUser&&$kycUser->status!="RESUBMISSION"){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}

		DB::beginTransaction();

		try{

			$user=User::where('id',$userId)
						->update(['email'=>$request->email,'address'=>$request->address,'city'=>$request->city,'state'=>$request->state,'pincode'=>$request->pincode,'kyc_status'=>"PROCESSING"]);

			if($kycUser){
				$kycUser=UserKyc::where('user_id',$userId)
								->update(['user_name'=>$request->user_name,'user_profile'=>$profile,'pan_front'=>$panFront,'pan_back'=>$panBack,'status'=>"PROCESSING"]);
			}else{
				$data=[];
				$data['user_id']=$userId;
				$data['user_name']=$request->user_name;
				$data['user_profile']=$profile;
				$data['pan_front']=$panFront;
				$data['pan_back']=$panBack;
				$data['status']="PROCESSING";
	
				$kycUser=UserKyc::create($data);
			}
			$kycUser=UserKyc::where('user_id',$userId)->first();
			
			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$kycUser]);

		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>null]);
		}
	}


	//24. Get Upcoming Tickets
	public function get_upcoming_tickets($userId){
		$currentGame=CurrentGame::first();
		$gameDate=$currentGame->game_date;
		$gameTime=$currentGame->game_time;
		$gameDateTime=$currentGame->game_datetime;

		// $ticketsGameDay=GameTicket::where('user_id',$userId)
		// 				->where('game_date',$gameDate)
		// 				->whereTime('game_time','>=',$gameTime)
		// 				->orderBy('game_date')
		// 				->orderBy('game_time');

        $tickets=GameTicket::where('user_id',$userId)
						->where('game_datetime','>=',$gameDateTime)
                        // ->union($ticketsGameDay)
						->orderBy('game_datetime','ASC')
						->get()->groupBy(['game_date','game_time']);


		$data=[];
		foreach($tickets as $datewise){
			$count=0;
			$date="";
			foreach($datewise as $timewise){
				if(count($data)<2){
					$count=count($timewise);
					$date=$timewise[0]['game_date'].' '.$timewise[0]['game_time'];
					$tktData=[];
					$tktData['datetime']=$date;
					$tktData['count']=$count;
					array_push($data,$tktData);
				}
			}
		}
        
        usort($data, function($a, $b)
        {
            return strcmp($a['datetime'], $b['datetime']);
        });

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$data]);	
	}

	//25. Get Settings
	public function get_user_setting(){
		$setting=Setting::where('id',1)
						->select('call_duration','max_withdrawal','min_kyc','min_withdrawal','merchant_id','contact_email','contact_whatsapp',
							'terms_conditions','privacy_policy','refund_policy','about_us','contact_us')
						->first();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting]);		
	}


	//26. Get Paytm Token
	public function paytm_token(Request $request){
		$paytmParams = array();

		$amount=$request->amount;
		$setting=Setting::where('id',1)->first();
		$mid=$setting->merchant_id;
		$orderId=$request->order_id;
		$userId=$request->user_id;

		$transactionCount=Transaction::count()+1;
		$txnId="TXNID".$transactionCount;

		$paytmParams["body"] = array(
			"requestType"   => "Payment",
			"mid"           => $mid,
			"websiteName"   => "DEFAULT",
			"orderId"       => $orderId,
			"callbackUrl"   => "https://merchant.com/callback",
			"txnAmount"     => array(
				"value"     => $amount,
				"currency"  => "INR",
			),
			"userInfo"      => array(
				"custId"    => "CUST_001",
			),
		);

		DB::beginTransaction();

		try{

			$transactionData=[];
			$transactionData['order_id']=$orderId;
			$transactionData['txn_id']=$txnId;
			$transactionData['user_id']=$userId;
			$transactionData['txn_mode']="PAYTM";
			$transactionData['txn_type']="ADD";
			$transactionData['txn_status']="PENDING";
			$transactionData['txn_title']="Add Money to wallet";
			$transactionData['txn_sub_title']="Processing transaction";
			$transactionData['txn_admin_title']="Add Money by User";
			$transactionData['txn_message']="Wait for the transaction status to change, if it doesn't changes contact support";
			$transactionData['txn_amount']=$amount;
			$transactionData['closing_balance']=0;

			$transaction=Transaction::create($transactionData);

			/*
		* Generate checksum by parameters we have in body
		* Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
		*/
		$checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $setting->merchant_key);

		$paytmParams["head"] = array(
			"signature"    => $checksum
		);

		$post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

		/* for Staging */
		// $url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=".$mid."&orderId=".$orderId;

		/* for Production */
		$url = "https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=".$mid."&orderId=".$orderId;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json")); 
		$response = curl_exec($ch);
		DB::commit();

		return $response;
		}catch(Exception $e){
			DB::rollBack();

			return null;
		}
	}



	//27. Get Informations
	public function get_info($tag){
		$setting=Setting::where('id',1)->first();
		if($tag=="TERMS"){
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting->terms_conditions]);
		}else if($tag=="PRIVACY"){
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting->privacy_policy]);
		}else if($tag=="REFUND"){
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting->refund_policy]);
		}else if($tag=="ABOUTUS"){
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting->about_us]);
		}else if($tag=="CONTACTUS"){
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting->contact_us]);
		}else{
			return null;
		}
	}


	//28. Get My Ticket Numbers
	public function get_my_ticket_numbers($userId,$type){
		
		$gameTickets=GameTicket::where('game_datetime',$type)
							->where('user_id',$userId)
							->pluck('ticket_number');

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$gameTickets]);		
	}


	//29. Get Transaction by ID
	public function get_transaction_by_id($txnId){
		$transaction=Transaction::leftjoin('transaction_payouts','transaction_payouts.txn_id','transactions.txn_id')
							->where('transactions.txn_id',$txnId)
							->select('transactions.*','transaction_payouts.account_name','transaction_payouts.account_number','transaction_payouts.account_ifsc')
							->first();
		

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);		
	}

	//30. Add Money Webhook
	public function update_paytm_transaction(Request $request){
		$orderId=$request->ORDERID;
		$status=$request->STATUS;

		$transaction=Transaction::where('order_id',$orderId)
								->first();
		$admin=Admin::first();

		DB::beginTransaction();

		try{
			$user=User::where('id',$transaction->user_id)
						->first();
			$userBalance=$user->wallet_balance;
			$adminBalance=$admin->wallet_balance;
				if($transaction->txn_status=="PENDING"){
					if($status=="TXN_SUCCESS"){
						$updatedBalance=$userBalance+$transaction->txn_amount;
						$user->wallet_balance=$updatedBalance;
						$admin->wallet_balance=$adminBalance+$transaction->txn_amount;
						$transaction->closing_balance=$updatedBalance;
						$transaction->txn_title="Add Money to wallet";
						$transaction->txn_sub_title="Money added successfully";
						$transaction->txn_admin_title="Add Money by user";
						$transaction->txn_message="";
						$transaction->txn_status="SUCCESS";
						$transaction->save();
						$user->save();
						$admin->save();

					}else if($status=="TXN_FAILURE"){
						$transaction->txn_title="Add Money to wallet";
						$transaction->txn_sub_title="Failed to add money";
						$transaction->txn_admin_title="Add Money by user";
						$transaction->txn_message="If the amount is deducted from your account, the amount will be refunded back to your account in 7-10 working days.";
						$transaction->txn_status="FAILED";
						$transaction->save();
					}
				}
			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}
	}

	//31. Check add money status
	public function check_add_money_status($orderId){
		$setting=Setting::first();
		$mid=$setting->merchant_id;
		$mKey=$setting->merchant_key;

		/* initialize an array */
		$paytmParams = array();

		/* body parameters */
		$paytmParams["body"] = array(

			/* Find your MID in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys */
			"mid" => $mid,

			/* Enter your order id which needs to be check status for */
			"orderId" => $orderId,
		);

		/**
		* Generate checksum by parameters we have in body
		* Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
		*/
		$checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $mKey);

		/* head parameters */
		$paytmParams["head"] = array(
			/* put generated checksum value here */
			"signature"	=> $checksum
		);

		/* prepare JSON string for request */
		$post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

		/* for Staging */
		// $url = "https://securegw-stage.paytm.in/v3/order/status";

		/* for Production */
		$url = "https://securegw.paytm.in/v3/order/status";

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));  
		$response = curl_exec($ch);

		$responseData=json_decode($response,JSON_UNESCAPED_SLASHES);
		$status=$responseData['body']['resultInfo']['resultStatus'];

		$transaction=Transaction::where('order_id',$orderId)
								->first();
		$admin=Admin::first();

		DB::beginTransaction();

		try{
			$user=User::where('id',$transaction->user_id)
						->first();
			$userBalance=$user->wallet_balance;
			$adminBalance=$admin->wallet_balance;
				if($transaction->txn_status=="PENDING"){
					if($status=="TXN_SUCCESS"){
						$updatedBalance=$userBalance+$transaction->txn_amount;
						$user->wallet_balance=$updatedBalance;
						$admin->wallet_balance=$adminBalance+$transaction->txn_amount;
						$transaction->closing_balance=$updatedBalance;
						$transaction->txn_title="Add Money to wallet";
						$transaction->txn_sub_title="Money added successfully";
						$transaction->txn_admin_title="Add Money by user";
						$transaction->txn_message="";
						$transaction->txn_status="SUCCESS";
						$transaction->save();
						$user->save();
						$admin->save();

					}else if($status=="TXN_FAILURE"){
						$transaction->txn_title="Add Money to wallet";
						$transaction->txn_sub_title="Failed to add money";
						$transaction->txn_admin_title="Add Money by user";
						$transaction->txn_message="If the amount is deducted from your account, the amount will be refunded back to your account in 7-10 working days.";
						$transaction->txn_status="FAILED";
						$transaction->save();
					}
				}
			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}


	}



	//32. Submit withdrawal request
	public function submit_withdrawal_request(Request $request){
		$setting=Setting::first();
		$mid=$setting->merchant_id;
		$mKey=$setting->merchant_key;
		$guid=$setting->account_number;
		$orderId=$request->order_id;
		$userId=$request->user_id;
		$amount=$request->amount;
		$accountName=$request->account_name;
		$account=$request->account_number;
		$ifsc=$request->account_ifsc;


		$transactionCount=Transaction::count()+1;
		$txnId="TXNID".$transactionCount;

		$paytmParams = array();

		$paytmParams["subwalletGuid"]      = $guid;
		$paytmParams["orderId"]            = $orderId;
		$paytmParams["beneficiaryAccount"] = $account;
		$paytmParams["beneficiaryIFSC"]    = $ifsc;
		$paytmParams["amount"]             = $amount;
		$paytmParams["purpose"]            = "OTHERS";

		$post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);


		$transactionData=[];
		$transactionData['order_id']=$orderId;
		$transactionData['txn_id']=$txnId;
		$transactionData['user_id']=$userId;
		$transactionData['txn_mode']="PAYTM";
		$transactionData['txn_type']="WITHDRAW";
		$transactionData['txn_amount']=$amount;

		$user=User::where('id',$request->user_id)->first();

		if($user->withdrawal_amount+$amount>=50000&&$user->kyc_status!="VERIFIED"){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_04','data'=>null]);
		}

		DB::beginTransaction();

		try{
			$admin=Admin::first();

			$userBalance=$user->wallet_balance;
			$adminBalance=$admin->wallet_balance;

				if($user->wallet_balance<$amount){
					DB::rollBack();
					return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
				}else if($amount>100000){
					return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>null]);
				}



				$checksum = PaytmChecksum::generateSignature($post_data, $mKey);

				$x_mid      = $mid;
				$x_checksum = $checksum;

				/* for Staging */
				$url = "https://staging-dashboard.paytm.com/bpay/api/v1/disburse/order/bank";

				/* for Production */
				// $url = "https://dashboard.paytm.com/bpay/api/v1/disburse/order/bank";

				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "x-mid: " . $x_mid, "x-checksum: " . $x_checksum)); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
				$response = curl_exec($ch);

				$responseData=json_decode($response,JSON_UNESCAPED_SLASHES);
				
				if($responseData['status']=="ACCEPTED"||$responseData['status']=="PENDING"){
					$transactionData['txn_status']="PENDING";
					$transactionData['txn_title']="Withdraw money from wallet";
					$transactionData['txn_sub_title']="Withdrawal request submitted";
					$transactionData['txn_admin_title']="Withdrawal by user";
					$transactionData['txn_message']="The withdrawal request is pending and will be completed in 2-3 business days. If you need any assistant, contact support.";
					$transactionData['closing_balance']=$userBalance-$amount;
					$user->wallet_balance=$userBalance-$amount;
					$admin->wallet_balance=$adminBalance-$amount;
					$user->withdrawal_amount=$user->withdrawal_amount+$amount;
					$user->save();
					$admin->save();
					
				}else if($responseData['status']=="SUCCESS"){
					$transactionData['txn_status']="SUCCESS";
					$transactionData['txn_title']="Withdraw money from wallet";
					$transactionData['txn_sub_title']="Amount successfully withdrawn from wallet";
					$transactionData['txn_admin_title']="Withdrawal by User";
					$transactionData['txn_message']="The amount is successfully sent to the given bank details.";
					$transactionData['closing_balance']=$userBalance-$amount;
					$user->wallet_balance=$userBalance-$amount;
					$admin->wallet_balance=$adminBalance-$amount;
					$user->withdrawal_amount=$user->withdrawal_amount+$amount;
					$user->save();
					$admin->save();
					
				}else if($responseData['status']=="FAILURE"){
					$transactionData['txn_status']="FAILED";
					$transactionData['txn_title']="Failed to withdraw";
					$transactionData['txn_sub_title']="Withdrawal request failed";
					$transactionData['txn_admin_title']="Withdrawal request by user";
					$transactionData['txn_message']="The withdrawal request is failed and the amount is credited back to app wallet. Try again or contact support";
					$transactionData['closing_balance']=$userBalance;
					$user->wallet_balance=$userBalance+$amount;
					$admin->wallet_balance=$adminBalance+$amount;
					$user->withdrawal_amount=$user->withdrawal_amount-$amount;
					$user->save();
					$admin->save();
				}

				

				$transaction=Transaction::create($transactionData);

				$transaction->fresh();
				$payoutData=[];
				$payoutData['txn_id']=$txnId;
				$payoutData['user_id']=$userId;
				$payoutData['account_name']=$accountName;
				$payoutData['account_number']=$account;
				$payoutData['account_ifsc']=$ifsc;

	
				$transactionPayout=TransactionPayout::create($payoutData);
			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_03','data'=>null]);
		}
	}


	//33. Withdrawal Request Webhook
	public function withdrawal_webhook(Request $request){
		$status=$request->status;
		$orderId=$request['result']['orderId'];

		$transaction=Transaction::where('order_id',$orderId)->first();

		try{
			$user=User::where('id',$request->user_id)->first();
			$admin=Admin::first();

			$userBalance=$user->wallet_balance;
			$adminBalance=$admin->wallet_balance;

			if($transaction->txn_status=="PENDING"){
				if($status=="SUCCESS"){
					$transaction->txn_status="SUCCESS";
				   $transaction->txn_title="Withdraw money from wallet";
				   $transaction->txn_sub_title="Amount successfully withdrawn from wallet";
				   $transaction->txn_admin_title="Withdrawal by User";
				   $transaction->txn_message="The amount is successfully sent to the given bank details.";
				   $transaction->save();
			   }else if($status=="FAILURE"){
				   $transaction->txn_status="FAILED";
				   $transaction->txn_title="Failed to withdraw";
				   $transaction->txn_sub_title="Withdrawal request failed";
				   $transaction->txn_admin_title="Withdrawal request by user";
				   $transaction->txn_message="The withdrawal request is failed and the amount is credited back to app wallet. Try again or contact support";
				   $transaction->closing_balance=$transaction->closing_balance-$transaction->txn_amount;
				   $user->wallet_balance=$userBalance+$transaction->txn_amount;
				   $admin->wallet_balance=$adminBalance+$transaction->txn_amount;
				   $user->save();
				   $admin->save();
				   $transaction->save();
			   }

			}

			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_03','data'=>null]);
		}

	}


	//34. Check Withdrawal Status
	public function check_withdrawal_status(Request $request){

		$setting=Setting::first();
		$mid=$setting->merchant_id;
		$mKey=$setting->merchant_key;
		$orderId=$request->order_id;
		$userId=$request->user_id;


		$paytmParams = array();

		$paytmParams["orderId"] = $orderId;

		$post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

		/*
		* Generate checksum by parameters we have in body
		* Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
		*/
		$checksum = PaytmChecksum::generateSignature($post_data, $mKey);

		$x_mid      = $mid;
		$x_checksum = $checksum;

		/* for Staging */
		$url = "https://staging-dashboard.paytm.com/bpay/api/v1/disburse/order/query";

		/* for Production */
		// $url = "https://dashboard.paytm.com/bpay/api/v1/disburse/order/query";

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "x-mid: " . $x_mid, "x-checksum: " . $x_checksum)); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$response = curl_exec($ch);

		$responseData=json_decode($response,JSON_UNESCAPED_SLASHES);

		$status=$responseData['status'];

		$transaction=Transaction::where('order_id',$orderId)->first();

		$user=User::where('id',$userId)->first();
			$admin=Admin::first();

			$userBalance=$user->wallet_balance;
			$adminBalance=$admin->wallet_balance;

			if($transaction->txn_status=="PENDING"){
				if($status=="SUCCESS"){
					$transaction->txn_status="SUCCESS";
				   $transaction->txn_title="Withdraw money from wallet";
				   $transaction->txn_sub_title="Amount successfully withdrawn from wallet";
				   $transaction->txn_admin_title="Withdrawal by User";
				   $transaction->txn_message="The amount is successfully sent to the given bank details.";
				   $transaction->save();
			   }else if($status=="FAILURE"){
				   $transaction->txn_status="FAILED";
				   $transaction->txn_title="Failed to withdraw";
				   $transaction->txn_sub_title="Withdrawal request failed";
				   $transaction->txn_admin_title="Withdrawal request by user";
				   $transaction->txn_message="The withdrawal request is failed and the amount is credited back to app wallet. Try again or contact support";
				   $transaction->closing_balance=$transaction->closing_balance-$transaction->txn_amount;
				   $user->wallet_balance=$userBalance+$transaction->txn_amount;
				   $admin->wallet_balance=$adminBalance+$transaction->txn_amount;
				   $user->withdrawal_amount=$user->withdrawal_amount-$transaction->txn_amount;
				   $user->save();
				   $admin->save();
				   $transaction->save();
			   }
			}
			   
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
	}


	//35. Get my KYC
	public function get_my_kyc($userId){
		$kyc=UserKyc::where('user_id',$userId)->first();
		$user=User::where('id',$userId)->first();

		$userKycData=[];
		$userKycData['id']=$kyc->id;
		$userKycData['user_id']=$kyc->user_id;
		$userKycData['user_name']=$kyc->user_name;
		$userKycData['user_profile']=$kyc->user_profile;
		$userKycData['pan_front']=$kyc->pan_front;
		$userKycData['pan_back']=$kyc->pan_back;
		$userKycData['status']=$kyc->status;
		$userKycData['email']=$user->email;
		$userKycData['address']=$user->address;
		$userKycData['city']=$user->city;
		$userKycData['state']=$user->state;
		$userKycData['pincode']=$user->pincode;
		$userKycData['created_at']=$kyc->created_at;
		$userKycData['updated_at']=$kyc->updated_at;

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$userKycData]);
	}

	//36. Get Homepage Details
	public function get_homepage_details($userId){
		$currentGame=CurrentGame::where('id',1)->first();
		$user=User::where('id',$userId)->first();

		$now=new Carbon();
		$date=$now->toDateString();
		$time=$now->toTimeString();

		$gameTime=new Carbon($currentGame->game_datetime);

		$currentGameData=[];

		$currentGameData['wallet_balance']=$user->wallet_balance;
		$currentGameData['locked_balance']=$user->locked_balance;
		$currentGameData['user_status']=$user->is_blocked;


		if($gameTime<$now&&$currentGame->game_status!="WAITING"&&$currentGame->game_status!="STARTED"){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>$currentGameData]);
		}

		$currentGameData['date']=$date;
		$currentGameData['time']=$time;
		$currentGameData['datetime']=$now->toDateTimeString();
		$currentGameData['game_date']=$currentGame->game_date;
		$currentGameData['game_time']=$currentGame->game_time;
		$currentGameData['game_datetime']=$currentGame->game_datetime;
		$currentGameData['ticket_price']=$currentGame->ticket_price;
		$currentGameData['game_status']=$currentGame->game_status;
		$currentGameData['bumper']=$currentGame->bumper;
		$currentGameData['booking_close_minutes']=$currentGame->booking_close_minutes;

		$gameTime->addMinutes(-($currentGame->booking_close_minutes));

		$bookingCloseTime=$gameTime->toDateTimeString();
		$currentGameData['booking_close_time']=$bookingCloseTime;

		$now=new Carbon();
		$bumperGame=Game::where('game_datetime','>=',$now)
						->where('type','BUMPER')
						->orderBy('game_datetime','DESC')
						->first();

		if($bumperGame){
			$currentGameData['bumper_game_date']=$bumperGame->game_date;
			$currentGameData['bumper_game_time']=$bumperGame->game_time;
			$currentGameData['bumper_ticket_price']=$bumperGame->ticket_price;
			$currentGameData['bumper_game_datetime']=$bumperGame->game_datetime;
		}else{
			$currentGameData['bumper_game_date']="-";
			$currentGameData['bumper_game_time']="-";
			$currentGameData['bumper_ticket_price']=0;
			$currentGameData['bumper_game_datetime']="-";
		}
		

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$currentGameData]);
	}



	//37. Submit withdrawal request to admin
	public function submit_withdraw_request_admin(Request $request){
		$orderId=$request->order_id;
		$userId=$request->user_id;
		$amount=$request->txn_amount;
		$accountName=$request->account_name;
		$account=$request->account_number;
		$ifsc=$request->account_ifsc;


		$setting=Setting::first();
		$transactionCount=Transaction::count()+1;
		$txnId="TXNID".$transactionCount;

		$user=User::where('id',$request->user_id)->first();

		if($amount<($setting->min_withdrawal)){
			return response()->json(['status' => 'FAILED', 'code' => 'FC_03', 'data' => null]);
		}
		

		if($user->withdrawal_amount+$amount>=50000&&$user->kyc_status!="VERIFIED"){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_04','data'=>null]);
		}

		$userBalance=$user->wallet_balance;
		$lockedBalance=$user->locked_balance;

		if(($userBalance-$lockedBalance)<$amount){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}

		DB::beginTransaction();

		try{
			
		$admin=Admin::first();

		$adminBalance=$admin->wallet_balance;

		$transactionData=[];
		$transactionData['order_id']=$orderId;
		$transactionData['txn_id']=$txnId;
		$transactionData['user_id']=$userId;
		$transactionData['txn_mode']="PAYTM";
		$transactionData['txn_type']="WITHDRAW";
		$transactionData['txn_amount']=$amount;
		$transactionData['reference_id']="-";
		$transactionData['txn_status']="PENDING";
		$transactionData['txn_title']="Withdraw money from wallet";
		$transactionData['txn_sub_title']="Withdrawal request submitted";
		$transactionData['txn_admin_title']="Withdrawal by user";
		$transactionData['txn_message']="The withdrawal request is pending and will be completed in 2-3 business days. If you need any assistant, contact support.";
		$transactionData['closing_balance']=$userBalance-$amount;
		$transactionData['account_number']=$account;
		$transactionData['account_name']=$accountName;
		$transactionData['account_ifsc']=$ifsc;
		$user->wallet_balance=$userBalance-$amount;
		$admin->wallet_balance=$adminBalance-$amount;
		$user->withdrawal_amount=$user->withdrawal_amount+$amount;
		$user->save();
		$admin->save();

		$transaction=Transaction::create($transactionData);

		$transaction->refresh();
		$payoutData=[];
		$payoutData['txn_id']=$txnId;
		$payoutData['user_id']=$userId;
		$payoutData['account_name']=$accountName;
		$payoutData['account_number']=$account;
		$payoutData['account_ifsc']=$ifsc;

	
		$transactionPayout=TransactionPayout::create($payoutData);

			DB::commit();

			$balanceData=[];
			$balanceData['user_id']=$user->id;
			$balanceData['wallet_balance']=$user->wallet_balance;
			$balanceData['locked_balance']=$user->locked_balance;
		
			$this->sendWalletBalance($balanceData);
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_03','data'=>null]);
		}
	}

	public function sendWalletBalance($data){
		$data_string = json_encode($data);

			$ch = curl_init('https://maxtambola.com:8080/sendWalletBalance');
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen($data_string)
				)
			);

			curl_exec($ch) . "\n";
			curl_close($ch);
	}


	//38. Update withdrawal request
	public function update_withdrawal_request_admin(Request $request){
		$status=$request->status;
		$txnId=$request->txn_id;
		

		$transaction=Transaction::where('txn_id',$txnId)->first();
		$amount=$transaction->txn_amount;

		if($request->has("message"))
		$message=$request->message;
		else $message=$transaction->txn_message;

		try{
			$user=User::where('id',$transaction->user_id)->first();
			$admin=Admin::first();

			$userBalance=$user->wallet_balance;
			$adminBalance=$admin->wallet_balance;

			if($transaction->txn_status=="QUEUED"){
				if($status=="SUCCESS"){
					$transaction->txn_status="SUCCESS";
				   $transaction->txn_title="Withdraw money from wallet";
				   $transaction->txn_sub_title="Amount successfully withdrawn from wallet";
				   $transaction->txn_admin_title="Withdrawal by User";
				   $transaction->txn_message=$message;
				   $transaction->save();
			   }else if($status=="FAILED"){
				   $transaction->txn_status="FAILED";
				   $transaction->txn_title="Failed to withdraw";
				   $transaction->txn_sub_title="Withdrawal request failed";
				   $transaction->txn_admin_title="Withdrawal request by user";
				   $transaction->txn_message=$message;
				   $transaction->closing_balance=$transaction->closing_balance-$transaction->txn_amount;
				   $user->wallet_balance=$userBalance+$transaction->txn_amount;
				   $admin->wallet_balance=$adminBalance+$transaction->txn_amount;
				   $user->save();
				   $admin->save();
				   $transaction->save();
			   }

			   $setting=Setting::first();
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


			}else{
				DB::rollBack();
				return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>null]);
			}

			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}
	}

	//39. Get Transaction Payout
	public function get_transaction_payout($txnId){
		$transactionPayout=TransactionPayout::where('txn_id',$txnId)->first();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transactionPayout]);
	}


	//40 Send notification
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
}





