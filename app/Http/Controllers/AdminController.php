<?php

namespace App\Http\Controllers;

use App\Classes\GenerateTicket;
use App\Models\Admin;
use App\Models\BumperPrize;
use App\Models\BumperTicket;
use App\Models\CacheHistory;
// use App\Models\CacheHistory;
use App\Models\CurrentGame;
use App\Models\DefaultMessage;
use App\Models\Game;
use App\Models\GameClaim;
use App\Models\GameJoin;
use App\Models\GameTicket;
use App\Models\Notification;
use App\Models\PaymentSetting;
use App\Models\PlayedGame;
use App\Models\Prize;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserKyc;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Error\Notice;

class AdminController extends Controller
{
	//User Adds Money +AW
	//User Withdraws -AW
	//User Purchase Ticket +AW
	//Prize Claim -AW
	//Refund Tickets -AW
	//Cancel Game - No change
	//Add Money by admin -AW
	//Withdraw money from user +AW

	

    //1. Login Admin
	//2. Find next Details
	//3. Get pending payments
	//4. Get all Active Game
	//5. Add Game Time & Bumper Game time
	//6. Delete Game
	//7. Update Game
	//8. Get Payment by status (Search by Transaction ID)
	//9. Add & Withdraw - Admin Wallet
	//10. Get all users (Search User)
	//11. Block Users
	//12. Add & Withdraw - User wallet
	//13. Get sold tickets by date & time
	//14. Get ticket by sold user... 
	//15. Get all users KYC
	//16. Game Prize Setting (Prize & Bumper)
	//17. Add schedule Notification
	//18. Edit notification
	//19. Delete notification
	//20. Cancel game & delete tickets
	//21. Cancel Game & reschedule
	//22. Get users transactions
	//23. Get users tickets

	//24. Get Settings
	//25. Update Settings
	//26. Get Active Game For Date & Time
	//27. Update KYC status
	//28. Randomize Tickets
	//29. Randomize Sequence
	//30. Check Game Status & Time
	//31. Send Notification
	//32. Send SMS
	//33. Get All Tickets
	//34. Get All Bumper Tickets
	//35. Randomize Bumper Tickets
	//36. Randomize Bumper Sequence
	
	//37. Get All Notifications
	//38. Cancel tickets by user
	//39. Reschedule tickets by user
	//40. Update Notification Status
	//41. Update Prize Setting
	//42. Update Prize Status
	//43. Get Game times for date
	//44. Get Admin Balance

	//45. Get Game Page Data
	//46. Get All Pending Transactions
	//47. Get default status message
	//48. Restart Server

	//Admin Api (Transaction status)
	//1. Change transaction status



	public function update_payment_setting(Request $request){
		$paymentSetting=PaymentSetting::first();

		if(!$paymentSetting){
			return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
		}

		DB::beginTransaction();
		try{

			$paymentSetting->account_number=$request->account_number;
			$paymentSetting->account_name=$request->account_name;
			$paymentSetting->account_ifsc=$request->account_ifsc;
			$paymentSetting->vpa_id=$request->vpa_id;
			$paymentSetting->is_payu_active=$request->is_payu_active;
			$paymentSetting->is_upi_active=$request->is_upi_active;
			$paymentSetting->is_manual_upi_active=$request->is_manual_upi_active;
			$paymentSetting->is_manual_bank_active=$request->is_manual_bank_active;
			$paymentSetting->is_manual_payment_active=$request->is_manual_payment_active;
			$paymentSetting->is_manual_cancel_active=$request->is_manual_cancel_active;
			$paymentSetting->is_withdraw_cancel_active=$request->is_withdraw_cancel_active;

			$paymentSetting->save();

			$cacheHistory=CacheHistory::first();
			$cacheHistory->payment_change_cn+=1;
			$cacheHistory->save();

			DB::commit();

			return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $paymentSetting]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
		}
	}


	//Change Transaction Status
	public function change_transaction_status(Request $request){
		$id=$request->id;
		$orderId=$request->order_id;
		$status=$request->status;
		$message=$request->message;

		$transaction=Transaction::where('id',$id)
							->where('order_id',$orderId)
							->first();

		if($transaction){
			$txnAmount=$transaction->txn_amount;
			$userId=$transaction->user_id;
			$txnType=$transaction->txn_type;

			if($txnType=="ADD"){
				//Update ADD money status
				if($transaction->txn_status=="PENDING"){
					//Transaction is pending
					if($status=="SUCCESS"){
						//Set add money successful
						$user=User::where('id',$userId)
									->first();

						if(!$user){
							//User account not found
							return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
						}

						$walletBalance=$user->wallet_balance;
						$updatedBalance=$walletBalance+$txnAmount;

						$user->wallet_balance=$updatedBalance;
						$user->save();

						$transaction->txn_status="SUCCESS";
						$transaction->txn_sub_title="Money added to wallet successfully";
						$transaction->txn_message=$message;
						$transaction->closing_balance=$updatedBalance;
						$transaction->save();
					}else if($status=="FAILED"){
						//Set add money failed
						$transaction->txn_status="FAILED";
						$transaction->txn_sub_title="Failed to add money to wallet";
						$transaction->txn_message=$message;
						$transaction->save();
					}else{
						//Cannot change the transaction status
						return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
					}

					return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $transaction]);

				}else if($transaction->txn_status=="SUCCESS"){
					//Transaction is Success
					if($status=="REFUND"){
						//Refund the transaction

						$user=User::where('id',$userId)
									->first();

						if(!$user){
							//User account not found
							return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
						}

						$walletBalance=$user->wallet_balance;
						$updatedBalance=$walletBalance-$txnAmount;

						if($updatedBalance<0){
							$updatedBalance=0;
						}

						$user->wallet_balance=$updatedBalance;
						$user->save();

						$transaction->txn_status="REFUND";
						$transaction->txn_sub_title="Rs ".$txnAmount." Refunded to bank successfully";
						$transaction->txn_message=$message;
						$transaction->closing_balance=$updatedBalance;
						$transaction->save();

						return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $transaction]);
					}else{
						//Unknown transaction status
						return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
					}
				}else{
					//Cannot change status
					return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
				}

			}else if($txnType=="WITHDRAW"){
				//Update WITHDRAW money status
				if($transaction->txn_status=="PENDING"){
					//Transaction is pending
					if($status=="SUCCESS"){
						//Set success status
						$transaction->txn_status="SUCCESS";
						$transaction->txn_sub_title="Amount transferred to bank successfully";
						$transaction->txn_message=$message;
						$transaction->save();

					}else if($status=="LOCKED"){
						//Set locked status
						$transaction->txn_status="LOCKED";
						$transaction->txn_sub_title="Withdrawal approved and amount locked for transfer";
						$transaction->txn_message=$message;
						$transaction->save();

					}else if($status=="FAILED"){
						//Set failed status

						$user=User::where('id',$userId)
									->first();

						if(!$user){
							//User account not found
							return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
						}

						$walletBalance=$user->wallet_balance;
						$updatedBalance=$walletBalance+$txnAmount;

						$user->wallet_balance=$updatedBalance;
						$user->save();

						$transaction->txn_status="FAILED";
						$transaction->txn_sub_title="Failed to approve withdrawal request";
						$transaction->txn_message=$message;
						$transaction->closing_balance=$updatedBalance;
						$transaction->save();

			
					}else{
						//Cannot change status
						return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
					}
					return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $transaction]);
				}else{
					//Cannot change status
					return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
				}
			}else{
				//Cannot perform operation
				return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
			}
		}else{
			//Transaction not found
			return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
		}
	}


	public function set_default_settings(){

		$admin=Admin::first();

		if(!$admin){
			$adminData=[];
			$adminData['username']="admin";
			$adminData['password']="admin1@";
			$adminData['wallet_balance']=0;
			$adminData['token']="";

			$admin=Admin::create($adminData);
		}

		$setting=Setting::first();

		if(!$setting){
			$settingData=[];
			$settingData['account_number']="test";
			$settingData['add_money_notification']=1;
			$settingData['prize_cliam_notification']=1;
			$settingData['ticket_notification']=1;
			$settingData['user_signup_notification']=1;
			$settingData['withdrawal_notification']=1;
			$settingData['call_duration']=10;
			$settingData['max_withdrawal']=100000;
			$settingData['min_kyc']=50000;
			$settingData['min_withdrawal']=200;
			$settingData['merchant_id']="test";
			$settingData['merchant_key']="test";
			$settingData['sms_api']="test";
			$settingData['gcm_auth']="test";
			$settingData['withdraw_mode']="NEFT";
			$settingData['terms_conditions']="test";
			$settingData['privacy_policy']="test";
			$settingData['refund_policy']="test";
			$settingData['about_us']="test";
			$settingData['contact_us']="test";
			$settingData['contact_email']="test";
			$settingData['contact_whatsapp']="123";

			$setting=Setting::create($settingData);
		}

		$finalPrizeData=[];

		$earlyFive=[];
		$earlyFive['prize_name']="Early Five";
		$earlyFive['prize_tag']="EARLY5";
		$earlyFive['prize_count']=1;
		$earlyFive['prize_amount']=1000;
		$earlyFive['status']=1;
		$earlyFive['prize_status']="NEW";

		array_push($finalPrizeData,$earlyFive);

		$hsb=[];
		$hsb['prize_name']="Half Sheet Bonus";
		$hsb['prize_tag']="HALFSHEETBONUS";
		$hsb['prize_count']=1;
		$hsb['prize_amount']=1000;
		$hsb['status']=1;
		$hsb['prize_status']="NEW";

		array_push($finalPrizeData,$hsb);

		$fsb=[];
		$fsb['prize_name']="Full Sheet Bonus";
		$fsb['prize_tag']="FULLSHEETBONUS";
		$fsb['prize_count']=1;
		$fsb['prize_amount']=1000;
		$fsb['status']=1;
		$fsb['prize_status']="NEW";

		array_push($finalPrizeData,$fsb);

		$corners=[];
		$corners['prize_name']="Corners";
		$corners['prize_tag']="CORNERS";
		$corners['prize_count']=1;
		$corners['prize_amount']=1000;
		$corners['status']=1;
		$corners['prize_status']="NEW";

		array_push($finalPrizeData,$corners);

		$topLine=[];
		$topLine['prize_name']="Top Line";
		$topLine['prize_tag']="TOPLINE";
		$topLine['prize_count']=1;
		$topLine['prize_amount']=1000;
		$topLine['status']=1;
		$topLine['prize_status']="NEW";

		array_push($finalPrizeData,$topLine);

		$middleLine=[];
		$middleLine['prize_name']="Middle Line";
		$middleLine['prize_tag']="MIDDLELINE";
		$middleLine['prize_count']=1;
		$middleLine['prize_amount']=1000;
		$middleLine['status']=1;
		$middleLine['prize_status']="NEW";

		array_push($finalPrizeData,$middleLine);

		$bottomLine=[];
		$bottomLine['prize_name']="Bottom Line";
		$bottomLine['prize_tag']="BOTTOMLINE";
		$bottomLine['prize_count']=1;
		$bottomLine['prize_amount']=1000;
		$bottomLine['status']=1;
		$bottomLine['prize_status']="NEW";

		array_push($finalPrizeData,$bottomLine);

		$fullHouse=[];
		$fullHouse['prize_name']="Full House";
		$fullHouse['prize_tag']="FULLHOUSE";
		$fullHouse['prize_count']=1;
		$fullHouse['prize_amount']=1000;
		$fullHouse['status']=1;
		$fullHouse['prize_status']="NEW";

		array_push($finalPrizeData,$fullHouse);

		$fullHouse2=[];
		$fullHouse2['prize_name']="Second House";
		$fullHouse2['prize_tag']="FULLHOUSE2";
		$fullHouse2['prize_count']=1;
		$fullHouse2['prize_amount']=1000;
		$fullHouse2['status']=1;
		$fullHouse2['prize_status']="NEW";

		array_push($finalPrizeData,$fullHouse2);

		$fullHouse3=[];
		$fullHouse3['prize_name']="Third House";
		$fullHouse3['prize_tag']="FULLHOUSE3";
		$fullHouse3['prize_count']=1;
		$fullHouse3['prize_amount']=1000;
		$fullHouse3['status']=1;
		$fullHouse3['prize_status']="NEW";

		array_push($finalPrizeData,$fullHouse3);

		Prize::truncate();
		$prizes=Prize::insert($finalPrizeData);

		BumperPrize::truncate();
		$bumperPrizes=BumperPrize::insert($finalPrizeData);

		$currentGame=CurrentGame::first();
		
		if(!$currentGame){
			$currentGameData=[];
			$currentGameData['game_date']="01-01-2021";
			$currentGameData['game_time']="10:00:00";
			$currentGameData['ticket_price']=0;
			$currentGameData['bumper']=0;
			
			$gameDateTime=new Carbon("01-01-2021 10:00:00");
			$currentGameData['game_datetime']=$gameDateTime;
			$currentGameData['last_game_date']="01-01-2021";
			$currentGameData['last_game_time']="10:00:00";
			$currentGameData['booking_close_minutes']=15;
			$currentGameData['last_game_datetime']=$gameDateTime;
			$currentGameData['force_change']=1;
			$currentGameData['booking_open']=0;
			$currentGameData['game_status']="WAITING";

			$currentGame=CurrentGame::create($currentGameData);
		}

		$tickets=$this->randomize_tickets();
		$bumperTickets=$this->randomize_bumper_tickets();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
	}


	//1. Login Admin
	public function login_admin(Request $request){
		$username=$request['username'];
		$password=$request['password'];

		$admin=Admin::where('username',$username)
					->where('password',$password)
					->select('username','wallet_balance','token')
					->first();
		if($admin){
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$admin]);
		}
		return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
	}

	//2. Get Next Game Details
	public function get_next_game_details(){
		$currentGame=CurrentGame::first();
		$now=new Carbon();
		$date=$now->toDateString();
		$time=$now->toTimeString();

		$gameTime=new Carbon($currentGame->game_datetime);

		if($gameTime<$now&&$currentGame->game_status!="PLAYING"){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}

		$totalTickets=GameTicket::where('game_date',$currentGame->game_date)
								->where('game_time',$currentGame->game_time)
								->count();

		$totalCollection=$currentGame->ticket_price*$totalTickets;

		$currentGame['date']=$date;
		$currentGame['time']=$time;
		$currentGame['datetime']=$now->toDateTimeString();

		$data=[];
		$data['current_game']=$currentGame;
		$data['total_tickets']=$totalTickets;
		$data['total_amount']=$totalCollection;

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$data]);
	}


	//3. Get Pending Payments
	public function get_pending_payments(Request $request){
		$from_id=$request->from_id;

		if($from_id==0){
			$transaction=Transaction::where('txn_status','PENDING')
			->orderBy('created_at','DESC')
			->limit(20)
			->get();
		}else{
			$transaction=Transaction::where('txn_status','PENDING')
								->where('id','<',$from_id)
								->orderBy('created_at','DESC')
								->limit(20)
								->get();
		}
		

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);					
	}


	//4. Get All Active Game
	public function get_active_game(){
		$now=new Carbon();

		$games=Game::where('game_datetime','>=',$now)
					->orderBy('game_datetime','ASC')
					->get();
	
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$games]);					
	}


	//5. Add Game Time & Bumper Game time
	public function add_game(Request $request){
		$gameCarbon=Carbon::createFromFormat('d-m-Y H:i:s',$request->game_date.' '.$request->game_time,'Asia/Calcutta');
		$gameCarbon->setTimezone('UTC');

		$gameDate=$gameCarbon->toDateString();
		$gameTime=$gameCarbon->toTimeString();
		$gameDateTime=$gameCarbon->toDateTimeString();

		$game=Game::where('game_date',$gameDate)
					->where('game_time',$gameTime)
					->first();
		
		if($game){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);					
		}

		DB::beginTransaction();
		try{
			$gameData=[];
			$gameData['game_date']=$gameDate;
			$gameData['game_time']=$gameTime;
			$gameData['game_datetime']=$gameDateTime;
			$gameData['type']=$request->type;
			$gameData['ticket_price']=$request->ticket_price;
			$gameData['status']=$request->status;
			$gameData['bumper']=$request->bumper;
			$gameData['booking_close_minutes']=$request->booking_close_minutes;

			$game=Game::create($gameData);

			// if($request->refresh_game==1){
				$this->refresh_game();
			// }

			DB::commit();

			$this->notifyAddGame($request->ticket_price,$gameDate,$gameTime);

			$this->sendCurrentGame();

			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$game]);					
		}catch(Exception $e){
			DB::rollBack();
		
			return response()->json(['status'=>'FAILED','code'=>'FC_02','data'=>null]);					
		}
	}

	public function notifyAddGame($ticketPrice,$gameDate,$gameTime){
		$users=User::where('is_blocked',0)
						->where('token','!=',null)
						->select('token','phone');


		$setting=Setting::first();
		$auth=$setting->gcm_auth;


		$finalTokens=[];
		$finalPhones=[];

		foreach($users as $user){
			if(!in_array($user['token'],$finalTokens)){
				array_push($finalTokens,$user['token']);
			}

			if(!in_array($user['phone'],$finalPhones)){
				array_push($finalPhones,$user['phone']);
			}
		}

		$message="New Game

		Please join and play, there is a game for Rs price ".$ticketPrice." on date ".$gameDate." at time ".$gameTime." LUDOPD";
		
		$numbers="";

		foreach($finalPhones as $phone){
			$numbers=$numbers."91".$phone.',';
		}

		$numbers=substr($numbers,0,-1);
		
		$this->sendMessage($message,$numbers,119991,"Promotional");

		$notification=$this->notification($finalTokens,"New Game",$message,$auth);

		return null;
	}

	public function sendCurrentGame(){
		$now=new Carbon();
		$currentGame=CurrentGame::first();

		$bumperGame=Game::where('game_datetime','>=',$now)
						->where('type','BUMPER')
						->orderBy('game_datetime','ASC')
						->first();

		$normalGame=Game::where('game_datetime','>=',$now)
						->where('type','NORMAL')
						->orderBy('game_datetime','ASC')
						->first();

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

		$date=$now->toDateString();
		$time=$now->toTimeString();

		$currentGame['date']=$date;
		$currentGame['time']=$time;
		$currentGame['datetime']=$now->toDateTimeString();

		$resultData=[];
		$resultData['normal_game']=$normalGame;
		$resultData['current_game']=$currentGame;
		if($currentGame->game_status=="COMPLETED"){
			$resultData['current_game']=null;
		}
		$resultData['bumper_game']=$bumperGame;

		$data_string = json_encode($resultData);

			$ch = curl_init('https://maxtambola.com:8080/sendCurrentGame');
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

			return null;
	}


	//6. Delete Game
	public function delete_game(Request $request){
		$gameCarbon=Carbon::createFromFormat('d-m-Y H:i:s',$request->game_date.' '.$request->game_time,'Asia/Calcutta');
		$gameCarbon->setTimezone('UTC');

		$gameDate=$gameCarbon->toDateString();
		$gameTime=$gameCarbon->toTimeString();
		$gameDateTime=$gameCarbon->toDateTimeString();

		DB::beginTransaction();
		try{

			$game=Game::where('game_date',$gameDate)
				->where('game_time',$gameTime)
				->delete();

			$this->refresh_game();

			DB::commit();

			$this->notifyDeleteGame($request->game_date,$request->game_time);

			$this->sendCurrentGame();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);					
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'FAILED','code'=>'FC_01','data'=>null]);					
		}
	}


	public function notifyDeleteGame($gameDate,$gameTime){
		$users=User::where('is_blocked',0)
						->where('token','!=',null)
						->select('token','phone');


		$setting=Setting::first();
		$auth=$setting->gcm_auth;


		$finalTokens=[];
		$finalPhones=[];

		foreach($users as $user){
			if(!in_array($user['token'],$finalTokens)){
				array_push($finalTokens,$user['token']);
			}

			if(!in_array($user['phone'],$finalPhones)){
				array_push($finalPhones,$user['phone']);
			}
		}

		$message="Technical issue

		The game for date ".$gameDate." at time ".$gameTime." is cancelled due to technical issue. {Refund/Reschedule} will be made as soon. Inconvenience caused is deeply regretted.LUDOPD";
		
		$numbers="";

		foreach($finalPhones as $phone){
			$numbers=$numbers."91".$phone.',';
		}

		$numbers=substr($numbers,0,-1);
		
		$this->sendMessage($message,$numbers,119990,"Promotional");

		$notification=$this->notification($finalTokens,"Game Cancelled",$message,$auth);

		return null;
	}


	//7. Update Game
	public function update_game(Request $request){

		$gameCarbon=Carbon::createFromFormat('d-m-Y H:i:s',$request->game_date.' '.$request->game_time,'Asia/Calcutta');
		$gameCarbon->setTimezone('UTC');

		$gameDate=$gameCarbon->toDateString();
		$gameTime=$gameCarbon->toTimeString();
		$gameDateTime=$gameCarbon->toDateTimeString();

		$game=Game::where('id',$request->id)
				->first();

		if(!$game){
			return response()->json(['status'=>'FAILED','code'=>'FC_01','data'=>null]);					
		}

		$oldDateTime=new Carbon($game->game_datetime);
		$oldDateTime->setTimezone('Asia/Calcutta');
		$oldDate=$oldDateTime->toDateString();
		$oldTime=$oldDateTime->toTimeString();

		DB::beginTransaction();

		try{$game->game_date=$gameDate;
			$game->game_time=$gameTime;
			$game->game_datetime=$gameDateTime;
			$game->ticket_price=$request->ticket_price;
			$game->status=$request->status;
			$game->booking_close_minutes=$request->booking_close_minutes;
			$game->type=$request->type;
			if($request->type=="BUMPER")
			$game->bumper=1;
			else $game->bumper=0;
	
			$game->save();
	
			$this->refresh_game();

			DB::commit();
	
			$this->notifyUpdateGame($oldDate,$oldTime,$request->game_date,$request->game_time);

			$this->sendCurrentGame();

			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$game]);					
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'FAILED','code'=>'FC_02','data'=>null]);					
		}
	}

	public function notifyUpdateGame($gameDate,$gameTime,$newDate,$newTime){
		$users=User::where('is_blocked',0)
						->where('token','!=',null)
						->select('token','phone');


		$setting=Setting::first();
		$auth=$setting->gcm_auth;


		$finalTokens=[];
		$finalPhones=[];

		foreach($users as $user){
			if(!in_array($user['token'],$finalTokens)){
				array_push($finalTokens,$user['token']);
			}

			if(!in_array($user['phone'],$finalPhones)){
				array_push($finalPhones,$user['phone']);
			}
		}

		

		$message="Game update

		The game for date ".$gameDate." at time ".$gameTime." has changed to date ".$newDate." at time ".$newTime." LUDOPD";
		
		$numbers="";

		foreach($finalPhones as $phone){
			$numbers=$numbers."91".$phone.',';
		}

		$numbers=substr($numbers,0,-1);
		
		$this->sendMessage($message,$numbers,119993,"Promotional");

		$notification=$this->notification($finalTokens,"Game Update",$message,$auth);

		return null;
	}


	//Refresh Game
	public function refresh_game(){
		$now=new Carbon();
		$currentGame=CurrentGame::first();
			
		$game=Game::where('game_datetime','>=',$now)
					->orderBy('game_datetime','ASC')
					->first();
		
		if($game&&$currentGame->game_status!="WAITING"&&$currentGame->game_status!="STARTED"){

			$currentGame->game_date=$game->game_date;
			$currentGame->game_time=$game->game_time;
			$currentGame->ticket_price=$game->ticket_price;
			if($game->type=="BUMPER")
				$currentGame->bumper=1;
			else $currentGame->bumper=0;
			$currentGame->game_datetime=$game->game_datetime;
			$currentGame->game_status="NEW";
			$gameCarbon=new Carbon($game->game_datetime);
			$gameCarbon->subMinutes($game->booking_close_minutes);

			$currentGame->booking_close_time=$gameCarbon->toDateTimeString();

			$currentGame->save();

		
			if($currentGame->bumper==1){
				BumperPrize::orderBy('id','ASC')->update(['prize_status'=>"NEW"]);
			}else{
				Prize::orderBy('id','ASC')->update(['prize_status'=>"NEW"]);
			}
		}

		$cacheHistory=CacheHistory::first();
		$cacheHistory->game_change_cn+=1;
		$cacheHistory->save();

		return null;
	}

	//8. Get Payment by status (Search by Transaction ID)
	public function get_transactions(Request $request){
		$status=$request->status;
		$search=$request->search;
		$from_id=$request->from_id;

		if($status=="ALL"){
			if($from_id==0){
				$transactions=Transaction::where('txn_id','like','%'.$search.'%')
				->orderBy('created_at','DESC')
				->limit(20)
				->get();
			}else{
				$transactions=Transaction::where('txn_id','like','%'.$search.'%')
									->where('id','<',$from_id)
									->orderBy('created_at','DESC')
									->limit(20)
									->get();
			}
			
		}else{
			if($from_id==0){
				$transactions=Transaction::where('txn_status',$status)
				->where('txn_id','like','%'.$search.'%')
				->orderBy('created_at','DESC')
				->limit(20)
				->get();
			}else{
				$transactions=Transaction::where('txn_status',$status)
				->where('txn_id','like','%'.$search.'%')
				->where('id','<',$from_id)
				->orderBy('created_at','DESC')
				->limit(20)
				->get();
			}
			
		}

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transactions]);					
	}


	//9. Add & Withdraw - Admin Wallet
	public function add_admin_transaction(Request $request){
		//Remove admin transaction system
		//Add Money Total
		//Withdraw Money Total
		//Claim Total
		
	}


	//10. Get all users (Search User)
	public function get_all_users(Request $request){
		$from_id=$request->from_id;
		$search=$request->search;

		if($from_id==0){
            $users=User::where(function($q) use($search){
				$q->where('user_name','like','%'.$search.'%')
					->orWhere('phone','like','%'.$search.'%');	
			})
            ->orderBy('created_at','DESC')
            ->limit(20)
            ->get();
        }else{
            $users=User::where(function($q) use($search){
				$q->where('user_name','like','%'.$search.'%')
					->orWhere('phone','like','%'.$search.'%');	
			})
            ->where('id','<',$from_id)
            ->orderBy('created_at','DESC')
            ->limit(20)
            ->get();
        }
		

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$users]);						
	}


	//11. Block Unblock Users
	public function block_unblock_user($userId){
		$user=User::where('id',$userId)
				->first();
		if($user){
			if($user->is_blocked==0){
				$user->is_blocked=1;
			}else{
				$user->is_blocked=0;
			}
			$user->save();
		}
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$user]);						
	}

	//12. Add & Withdraw - User wallet
	public function add_user_transaction(Request $request){
		DB::beginTransaction();

		$transactionCount=Transaction::count()+1;
		$txnId="TXNID".$transactionCount;
		$orderId="ORDERID".$transactionCount;
		$amount=$request->txn_amount;
		$isLocked=$request->is_locked;

		$admin=Admin::first();
		$adminBalance=$admin->wallet_balance;


		try{
			$transactionData=[];
			$transactionData['order_id']=$orderId;
			$transactionData['txn_id']=$txnId;
			$transactionData['user_id']=$request->user_id;
			$transactionData['txn_mode']="ADMIN";
			$transactionData['txn_type']=$request->txn_type;
			$transactionData['txn_status']="SUCCESS";
			$transactionData['txn_message']="";
			$transactionData['txn_amount']=$amount;
			$transactionData['txn_message']=$request->txn_message;
			$transactionData['reference_id']="-";
			$transactionData['account_number']="-";
			$transactionData['account_name']="-";
			$transactionData['account_ifsc']="-";

			$user=User::where('id',$request->user_id)
						->first();
			$userBalance=$user->wallet_balance;
			$lockedBalance=$user->locked_balance;

			if($request->txn_type=="WITHDRAW"){

				if($user->wallet_balance<$amount){
					DB::rollBack();
					return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
				}
				$transactionData['txn_title']="Withdrawn by Admin";
				$transactionData['txn_sub_title']="Withdrawn successfully by admin";
				$transactionData['txn_admin_title']="Withdraw Money from User";
				$transactionData['closing_balance']=$userBalance-$amount;
				$user->wallet_balance=$userBalance-$amount;
				if($isLocked==1){
					$updatedLockedBalance=$lockedBalance-$amount;
					if($updatedLockedBalance<0){
						$updatedLockedBalance=0;
					}
					$user->locked_balance=$updatedLockedBalance;
				}
				$admin->wallet_balance=$adminBalance+$amount;
			}else{
				$transactionData['txn_title']="Added by Admin";
				$transactionData['txn_sub_title']="Added successfully by admin";
				$transactionData['txn_admin_title']="Add Money to User";
				$transactionData['closing_balance']=$userBalance+$amount;
				$user->wallet_balance=$userBalance+$amount;
				if($isLocked==1){
					$updatedLockedBalance=$lockedBalance+$amount;
					if($updatedLockedBalance<0){
						$updatedLockedBalance=0;
					}
					$user->locked_balance=$updatedLockedBalance;
				}
				$admin->wallet_balance=$adminBalance-$amount;
			}
			$transaction=Transaction::create($transactionData);
			$user->save();
			$admin->save();
			$transaction->fresh();

			DB::commit();

			$balanceData=[];
			$balanceData['user_id']=$user->id;
			$balanceData['wallet_balance']=$user->wallet_balance;
			$balanceData['locked_balance']=$user->locked_balance;
		
			$this->sendWalletBalance($balanceData);

			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>null]);
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


	//13. Get sold tickets by date & time
	public function get_sold_tickets($date,$time){
		$gameCarbon=Carbon::createFromFormat('d-m-Y H:i:s',$date.' '.$time,'Asia/Calcutta');
		$gameCarbon->setTimezone('UTC');

		$gameTickets=GameTicket::join('users','users.id','game_tickets.user_id')
							->where('game_tickets.game_date',$gameCarbon->toDateString())
							->where('game_tickets.game_time',$gameCarbon->toTimeString())
							->select('users.user_name','game_tickets.*')
							->get()->groupBy('user_id');

		$data=[];
		foreach($gameTickets as $gameTicket){
			$userId=$gameTicket[0]['user_id'];
			$userName=$gameTicket[0]['user_name'];
			$createdAt=$gameTicket[0]['created_at'];
			$totalCount=count($gameTicket);
			$totalAmount=0;

			foreach($gameTicket as $ticket){
				$totalAmount+=$ticket->ticket_price;
			}

			$singleData=[];
			$singleData['user_id']=$userId;
			$singleData['user_name']=$userName;
			$singleData['created_at']=$createdAt;
			$singleData['total_count']=$totalCount;
			$singleData['total_amount']=$totalAmount;

			array_push($data,$singleData);
		}

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$data]);						
	}


	//14. Get ticket by sold user... 
	public function get_all_tickets_by_user($date,$time,$userId){
		$gameCarbon=Carbon::createFromFormat('d-m-Y H:i:s',$date.' '.$time,'Asia/Calcutta');
		$gameCarbon->setTimezone('UTC');

		$gameTickets=GameTicket::where('game_date',$gameCarbon->toDateString())
							->where('game_time',$gameCarbon->toTimeString())
							->where('user_id',$userId)
							->get();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$gameTickets]);	
	}


	//15. Get all users KYC
	public function get_users_kyc(Request $request){
		$search=$request->search;
		$from_id=$request->from_id;
		$status=$request->status;

		$kycUsers=UserKyc::where('status',$status)
						->where('user_name','like','%'.$search.'%')
						->where('id','>',$from_id)
						->orderBy('id','ASC')
						->limit(20)
						->get();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$kycUsers]);								
	}


	//16. Game Prize Setting (Prize & Bumper)
	public function get_prize_settings($type){
		if($type=="BUMPER"){
			$bumperPrizes=BumperPrize::get();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$bumperPrizes]);								
		}else{
			$prizes=Prize::get();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$prizes]);								
		}
	}


	//17. Add schedule Notification
	public function add_notification(Request $request){
		$notificationCarbon=Carbon::createFromFormat('H:i:s',$request->notification_time,'Asia/Calcutta');
		$notificationCarbon->setTimezone('UTC');

		$notification=Notification::where('notification_time',$notificationCarbon->toTimeString())
									->first();
		if($notification){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);								
		}
		$notification=Notification::create($request->all());
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$notification]);								
	}

	//18. Edit notification
	public function update_notification(Request $request){
		$notification=Notification::where('notification_time',$request->notification_time)
									->first();

		if($notification){
			$notification->notification_title=$request->notification_title;
			$notification->notification_message=$request->notification_message;
			$notification->send_to=$request->send_to;
			$notification->status=$request->status;

			$notification->save();
		}
									
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$notification]);								
	}


	//19. Delete notification
	public function delete_notification($notificationId){
		$notification=Notification::where('id',$notificationId)
								->delete();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);									
	}


	//20. Cancel game & delete tickets
	public function cancel_game_refund(Request $request){
		$gameCarbon=Carbon::createFromFormat('d-m-Y H:i:s',$request->game_date.' '.$request->game_time,'Asia/Calcutta');
		$gameCarbon->setTimezone('UTC');

		$gameDate=$gameCarbon->toDateString();
		$gameTime=$gameCarbon->toTimeString();
		$tokenList=[];
		$phoneList=[];

		$currentGame=CurrentGame::first();

		DB::beginTransaction();

		try{

			if($currentGame->game_date==$gameDate&&$currentGame->game_time==$gameTime){
				$currentGame->game_status="CANCELLED";
				$currentGame->save();
			}

			$gameTickets=GameTicket::where('game_date',$gameDate)
			->where('game_time',$gameTime)
			->get()
			->groupBy('transaction_id');

			$transactionCount=Transaction::count()+1;
			$txnId="TXNID".$transactionCount;
			$orderId="ORDERID".$transactionCount;

			foreach($gameTickets as $gt){
			$totalAmount=0;
			$ticketCount=count($gt);
			$userId=-1;
			$transactionId="";		
			foreach($gt as $gtt){
			$totalAmount+=$gtt['ticket_price'];
			$userId=$gtt['user_id'];
			$transactionId=$gtt['transaction_id'];
			}

			$user=User::where('id',$userId)->first();
			$admin=Admin::first();
			$adminBalance=$admin->wallet_balance;
			$walletBalance=$user->wallet_balance;
			$updatedBalance=$walletBalance+$totalAmount;

			if(!in_array($user->token,$tokenList)&&$user->token!=null){
				array_push($tokenList,$user->token);
			}

			if(!in_array($user->phone,$phoneList)){
				array_push($phoneList,$user->phone);
			}

			$transaction=Transaction::where('txn_id',$transactionId)
								->where('user_id',$userId)
								->update(['txn_type'=>"REFUND",'txn_message'=>"Game for date ".$gameDate." and time ".$gameTime." cancelled and refunded to wallet.",
											'closing_balance'=>$updatedBalance,'txn_title'=>"Refund for Cancelled Game",'txn_admin_title'=>"Amount refunded due to game cancellation",'txn_sub_title'=>$ticketCount." Ticket Refunded Successfully"]);

			// $transactionData=[];
			// $transactionData['order_id']=$orderId;
			// $transactionData['txn_id']=$txnId;
			// $transactionData['user_id']=$userId;
			// $transactionData['txn_mode']="WALLET";
			// $transactionData['txn_type']="PURCHASE";
			// $transactionData['txn_status']="SUCCESS";
			// $transactionData['txn_message']="Game for date ".$gameDate." and time ".$gameTime." cancelled and refunded to wallet.";
			// $transactionData['txn_amount']=$totalAmount;
			// $transactionData['closing_balance']=$updatedBalance;
			// $transactionData['created_at']=now();
			// $transactionData['updated_at']=now();
			// $transactionData['txn_title']="Game Cancel Refund";
			// $transactionData['txn_admin_title']="Refunded to User";
			// $transactionData['txn_sub_title']=$ticketCount." Ticket Refunded Successfully";

			// $transaction=Transaction::create($transactionData);
			$user->wallet_balance=$updatedBalance;
			$admin->wallet_balance=$adminBalance-$totalAmount;
			$admin->save();
			$user->save();

			$balanceData=[];
			$balanceData['user_id']=$user->id;
			$balanceData['wallet_balance']=$user->wallet_balance;
			$balanceData['locked_balance']=$user->locked_balance;
		
			$this->sendWalletBalance($balanceData);

			}

			$setting=Setting::first();
			$gameTickets=GameTicket::where('game_date',$gameDate)
						->where('game_time',$gameTime)
						->delete();

			
			$title="Game Cancelled for ".$gameDate.' '.$gameTime;
			$message="Amount of Rs ".$totalAmount.' is refunded to your MaxTambola wallet';
			

			array_push($tokenList,$admin->token);

			DB::commit();

			$this->notification($tokenList,$title,$message,$setting->gcm_auth);
			
			$smsMessage="Cancel game & Refund

			Game for old date ".$gameDate." at old time ".$gameTime." is cancelled and ticket amount is credited back to your wallet.ludo"; 

			$numbers="";

			foreach($phoneList as $phone){
				$numbers=$numbers."91".$phone.',';
			}

			$numbers=substr($numbers,0,-1);
			
			$this->sendMessage($smsMessage,$numbers,119995,"Promotional");

			$this->refresh_game();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);								
		}								
	}

	//21. Cancel Game & reschedule
	public function cancel_game_reschedule(Request $request){
		$gameCarbon=Carbon::createFromFormat('d-m-Y H:i:s',$request->game_date.' '.$request->game_time,'Asia/Calcutta');
		$gameCarbon->setTimezone('UTC');

		$gameDate=$gameCarbon->toDateString();
		$gameTime=$gameCarbon->toTimeString();

		$newGameCarbon=Carbon::createFromFormat('d-m-Y H:i:s',$request->date.' '.$request->time,'Asia/Calcutta');
		$newGameCarbon->setTimezone('UTC');

		$date=$newGameCarbon->toDateString();
		$time=$newGameCarbon->toTimeString();

		$tokenList=[];
		$phoneList=[];

		$gameDateTime=new Carbon($date.' '.$time);

		$currentGame=CurrentGame::first();

		DB::beginTransaction();

		try{

			if($currentGame->game_date==$gameDate&&$currentGame->game_time==$gameTime){
				$currentGame->game_status="CANCELLED";
				$currentGame->save();
			}
	
			$gameTickets=GameTicket::where('game_date',$gameDate)
								->where('game_time',$gameTime)
								->update(['game_date'=>$date,'game_time'=>$time,'game_datetime'=>$gameDateTime]);
	
			$title="Game for ".$gameDate.' '.$gameTime." rescheduled";
			$message="The new game time is ".$date.' '.$time;
			
			$gameTickets=GameTicket::where('game_date',$gameDate)
				->where('game_time',$gameTime)
				->get()
				->groupBy('transaction_id');
	
				foreach($gameTickets as $gt){
				$userId=$gt[0]['user_id'];
				$user=User::where('id',$userId)->first();
				if(!in_array($user->token,$tokenList)&&$user->token!=null){
					array_push($tokenList,$user->token);
				}
	
				if(!in_array($user->phone,$phoneList)){
					array_push($phoneList,$user->phone);
				}
				}
				
				DB::commit();

				$setting=Setting::first();

			$smsMessage="Cancel game & Reschedule

			Game for date ".$gameDate." at time ".$gameTime." is cancelled and rescheduled to date&time ".$date.$time." ludopd"; 

			$this->notification($tokenList,$title,$message,$setting->gcm_auth);

			$numbers="";

			foreach($phoneList as $phone){
				$numbers=$numbers."91".$phone.',';
			}

			$numbers=substr($numbers,0,-1);
			
			$this->sendMessage($smsMessage,$numbers,119994,"Promotional");

			$this->refresh_game();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);	
			
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'FAILED','code'=>'FC_01','data'=>null]);	
		}		
	}


	//22. Get users transactions
	public function get_user_transactions(Request $request){
		$search=$request->search;
		$from_id=$request->from_id;
		$userId=$request->user_id;

		if($from_id==0){
			$transactions=Transaction::where('user_id',$userId)
			->where('txn_id','like','%'.$search.'%')
			->orderBy('created_at','DESC')
			->limit(20)
			->get();
		}else{
			$transactions=Transaction::where('user_id',$userId)
			->where('txn_id','like','%'.$search.'%')
			->where('id','<',$from_id)
			->orderBy('created_at','DESC')
			->limit(20)
			->get();
		}

	
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transactions]);								
	}


	//23. Get users tickets
	public function get_user_tickets(Request $request){
		$userId=$request->user_id;
		$from_id=$request->from_id;
		$search=$request->search;

		if($from_id==0){
			$gameTickets=GameTicket::where('user_id',$userId)
							->where('ticket_number','like','%'.$search.'%')
							->orderBy('created_at','DESC')
							->limit(20)
							->get();
		}else{
			$gameTickets=GameTicket::where('user_id',$userId)
							->where('ticket_number','like','%'.$search.'%')
							->where('id','<',$from_id)
							->orderBy('created_at','DESC')
							->limit(20)
							->get();
		}
		
		
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$gameTickets]);	
	}

	//24. Get Settings
	public function get_setting(){
		$settings=Setting::where('id',1)
					->first();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$settings]);	
	}


	//25. Update Settings
	public function update_setting(Request $request){

		DB::beginTransaction();
		try{
			$setting=Setting::first();

			if($setting){
				// $setting->account_number=$request->account_number;
				$setting->add_money_notification=$request->add_money_notification;
				$setting->prize_cliam_notification=$request->prize_cliam_notification;
				$setting->ticket_notification=$request->ticket_notification;
				$setting->user_signup_notification=$request->user_signup_notification;
				$setting->withdrawal_notification=$request->withdrawal_notification;
				$setting->call_duration=$request->call_duration;
				$setting->max_withdrawal=$request->max_withdrawal;
				$setting->min_withdrawal=$request->min_withdrawal;
				$setting->contact_email=$request->contact_email;
				$setting->contact_whatsapp=$request->contact_whatsapp;
				$setting->is_automatic_pricing=$request->is_automatic_pricing;
				$setting->early5_percent=$request->early5_percent;
				$setting->topline_percent=$request->topline_percent;
				$setting->middleline_percent=$request->middleline_percent;
				$setting->bottomline_percent=$request->bottomline_percent;
				$setting->fullhouse_percent=$request->fullhouse_percent;
				$setting->fullhouse2_percent=$request->fullhouse2_percent;
				$setting->fullhouse3_percent=$request->fullhouse3_percent;
				$setting->corners_percent=$request->corners_percent;
				$setting->halfsheet_percent=$request->halfsheet_percent;


				$setting->save();

				$cacheHistory=CacheHistory::first();
				$cacheHistory->setting_change_cn+=1;
				$cacheHistory->save();
			}

			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting]);	
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'FAILED','code'=>'FC_01','data'=>null]);	
		}
	}


	//26. Get Active Game For Date & Time
	public function get_past_game($gameDate,$gameTime){
		$gameCarbon=Carbon::createFromFormat('d-m-Y H:i:s',$gameDate.' '.$gameTime,'Asia/Calcutta');
		$gameCarbon->setTimezone('UTC');

		$game_date=$gameCarbon->toDateString();
		$game_time=$gameCarbon->toTimeString();

		$playedGame=PlayedGame::where('game_date',$game_date)
							->where('game_time',$game_time)
							->first();
		if(!$playedGame){
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}
		
		$gameJoins=GameJoin::where('played_game_id',$playedGame->id)
							->get();
		$playerCount=count($gameJoins);
		$ticketCount=0;
		foreach($gameJoins as $gj){
			$ticketCount+=$gj->total_tickets;
		}
		
		$prizes=Prize::where('status',1)
					->get();
		$claims=GameClaim::where('game_date',$game_date)
						->where('game_time',$game_time)
						->get();
		$claimCompleted=Prize::where('prize_status','CLAIMED')
						->pluck('prize_tag');
		
		$data=[];
		$data['played_game']=$playedGame;
		$data['prizes']=$prizes;
		$data['claims']=$claims;
		$data['claimed']=$claimCompleted;
		$data['player_count']=$playerCount;
		$data['total_tickets']=$ticketCount;

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$data]);
	}


	//27. Update KYC status
	public function update_kyc_status(Request $request){
		$userId=$request->user_id;
		$status=$request->status;

		DB::beginTransaction();

		try{
			$user=User::where('id',$userId)
						->update(['kyc_status'=>$status]);
			$kycUser=UserKyc::where('user_id',$userId)
							->update(['status'=>$status]);

			$user=User::where('id',$userId)->first();
			$kyc=UserKyc::where('user_id',$userId)->first();
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
			
			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$userKycData]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}
	}


	//28. Randomize Tickets
	public function randomize_tickets(){

		DB::beginTransaction();
		try{
			$generateTickets=new GenerateTicket();

			$allTickets=[];
			$times=0;
			do{
				$allTickets=[];
				$times++;
				for($i=0;$i<100;$i++){
					$tickets=$generateTickets->generate_tickets();
					$allTickets=array_merge($allTickets,$tickets);
				}
				if($times>2){
					return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
				}
			}while(count(array_unique($allTickets))!=600);
	
			$finalData=[];
			for($i=0;$i<count($allTickets);$i++){
				$data=[];
				$data['ticket']=$allTickets[$i];
				$data['ticket_number']=$i+1;
				// $data['customer_name']="";
				// $data['customer_profile']="";
				// $data['ticket_status']="AVAILABLE";
	
				array_push($finalData,$data);
			}
	
			Ticket::truncate();
			Ticket::insert($finalData);
	
			$cacheHistory=CacheHistory::first();
			$cacheHistory->ticket_change_cn+=1;
			$cacheHistory->save();
	

			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'FAILED','code'=>'FC_01','data'=>null]);
		}
		
	}


	//29. Randomize Sequence
	public function randomize_sequence(){

		DB::beginTransaction();
		try{
			$tickets=Ticket::get();

			$allTickets=[];
			foreach($tickets as $ticket){
				array_push($allTickets,$ticket->ticket);
			}
	
			shuffle($allTickets);
	
			$finalData=[];
			for($i=0;$i<count($allTickets);$i++){
				$data=[];
				$data['ticket']=$allTickets[$i];
				$data['ticket_number']=$i+1;
				// $data['customer_name']="";
				// $data['customer_profile']="";
				// $data['ticket_status']="AVAILABLE";
	
				array_push($finalData,$data);
			}
	
			Ticket::truncate();
			Ticket::insert($finalData);
	
			$cacheHistory=CacheHistory::first();
			$cacheHistory->ticket_change_cn+=1;
			$cacheHistory->save();
	

			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'FAILED','code'=>'FC_01','data'=>null]);
		}
		
	}


	//30. Check Game Status & Time
	public function check_game_status(){
		$now=new Carbon();
		$date=$now->toDateString();
		$now->setSeconds(0);

		$time=$now->toTimeString();
		
		$currentGame=CurrentGame::first();

		$nowDateTime=$now->toDateTimeString();

		$gameDateCarbon=new Carbon($currentGame->game_datetime);
		$gameTimeMins5=$gameDateCarbon->subMinutes(5)->setSeconds(0)->toDateTimeString();
		$gameDateCarbon->addMinutes(5);
		$gameCloseCarbon=new Carbon($currentGame->booking_close_time);
		$gameTimeMinus2DateTime=$gameCloseCarbon->toDateTimeString();
		$gameDateTime=$gameDateCarbon->toDateTimeString();
		$gameTime=$gameDateCarbon->toTimeString();

		$notification=Notification::where('notification_time',$time)
								->first();
		if($notification){
			// Send Notification
			$title=$notification->notification_title;
			$message=$notification->notification_message;
			$sendTo=$notification->send_to;

			$data=[];
			$data['notification_title']=$title;
			$data['notification_message']=$message;
			$data['send_to']=$sendTo;

			$notification=$this->process_notifications($data);
		}

		if($gameTimeMins5==$nowDateTime){
			$minutes=$gameDateCarbon->diffInMinutes($now)+1;

			$gameCarbon=Carbon::createFromFormat('Y-m-d H:i:s',$gameDateTime,'UTC');
			$gameCarbon->setTimezone('Asia/Calcutta');

			$message="Game for your purchased ticket is going to start in ".$minutes."minutes. Join the ".$gameCarbon->toTimeString()." game to play & win.LUDOPD";

			$this->processMessage($message,$gameDateTime,113578,"Transactional");
		}

		$setting=Setting::first();

		if($setting->is_automatic_pricing==1){
			$ticketCount=GameTicket::where('game_datetime',$currentGame->game_datetime)
							->count();

			$earlyFivePercent=$setting->early5_percent;
			$toplinePercent=$setting->topline_percent;
			$middlelinePercent=$setting->middleline_percent;
			$bottomlinePercent=$setting->bottomline_percent;
			$cornersPercent=$setting->corners_percent;
			$fullhousePercent=$setting->fullhouse_percent;
			$fullhouse2Percent=$setting->fullhouse2_percent;
			$fullhouse3Percent=$setting->fullhouse3_percent;
			$halfsheetPercent=$setting->halfsheet_percent;

			$totalCollection=$ticketCount*$currentGame->ticket_price;

			$early5=(int)($totalCollection*$earlyFivePercent*0.01);
			$topline=(int)($totalCollection*$toplinePercent*0.01);
			$middleline=(int)($totalCollection*$middlelinePercent*0.01);
			$bottomline=(int)($totalCollection*$bottomlinePercent*0.01);
			$corners=(int)($totalCollection*$cornersPercent*0.01);
			$fullhouse=(int)($totalCollection*$fullhousePercent*0.01);
			$fullhouse2=(int)($totalCollection*$fullhouse2Percent*0.01);
			$fullhouse3=(int)($totalCollection*$fullhouse3Percent*0.01);
			$halfsheet=(int)($totalCollection*$halfsheetPercent*0.01);

			if($currentGame->bumper==1){
				BumperPrize::where('prize_tag',"EARLY5")
							->update(['prize_amount'=>$early5]);

				BumperPrize::where('prize_tag',"HALFSHEETBONUS")
							->update(['prize_amount'=>$halfsheet]);

				BumperPrize::where('prize_tag',"CORNERS")
							->update(['prize_amount'=>$corners]);

				BumperPrize::where('prize_tag',"TOPLINE")
							->update(['prize_amount'=>$topline]);

				BumperPrize::where('prize_tag',"MIDDLELINE")
							->update(['prize_amount'=>$middleline]);

				BumperPrize::where('prize_tag',"BOTTOMLINE")
							->update(['prize_amount'=>$bottomline]);

				BumperPrize::where('prize_tag',"FULLHOUSE")
							->update(['prize_amount'=>$fullhouse]);

				BumperPrize::where('prize_tag',"FULLHOUSE2")
							->update(['prize_amount'=>$fullhouse2]);

				BumperPrize::where('prize_tag',"FULLHOUSE3")
							->update(['prize_amount'=>$fullhouse3]);

			}else{
				Prize::where('prize_tag',"EARLY5")
							->update(['prize_amount'=>$early5]);

				Prize::where('prize_tag',"HALFSHEETBONUS")
							->update(['prize_amount'=>$halfsheet]);

				Prize::where('prize_tag',"CORNERS")
							->update(['prize_amount'=>$corners]);

				Prize::where('prize_tag',"TOPLINE")
							->update(['prize_amount'=>$topline]);

				Prize::where('prize_tag',"MIDDLELINE")
							->update(['prize_amount'=>$middleline]);

				Prize::where('prize_tag',"BOTTOMLINE")
							->update(['prize_amount'=>$bottomline]);

				Prize::where('prize_tag',"FULLHOUSE")
							->update(['prize_amount'=>$fullhouse]);

				Prize::where('prize_tag',"FULLHOUSE2")
							->update(['prize_amount'=>$fullhouse2]);

				Prize::where('prize_tag',"FULLHOUSE3")
							->update(['prize_amount'=>$fullhouse3]);
			}

			$cacheHistory=CacheHistory::first();
			$cacheHistory->prize_change_cn+=1;
			$cacheHistory->save();
		}

		//Check if now is less than current game date time then create a played game

		if($gameTimeMinus2DateTime<=$nowDateTime&&$currentGame->game_status!="CANCELLED"&&$currentGame->game_status!="WAITING"){
			$playedGame=PlayedGame::where('game_date',$currentGame->game_date)
								->where('game_time',$currentGame->game_time)
								->first();
			if(!$playedGame){
				DB::beginTransaction();
				try{

					$playedGameData=[];
					$playedGameData['game_date']=$currentGame->game_date;
					$playedGameData['game_time']=$currentGame->game_time;
					$playedGameData['game_datetime']=$currentGame->game_datetime;
					if($currentGame->bumper==1){
						$playedGameData['game_type']="BUMPER";
					}else{
						$playedGameData['game_type']="NORMAL";
					}
					$playedGameData['called_numbers']="";
					$numbers=[];
					for($i=1;$i<91;$i++){
						array_push($numbers,$i);
					}
					shuffle($numbers);
					shuffle($numbers);
					$ticket="";
					foreach($numbers as $number){
						$ticket=$ticket.$number.",";
					}
					$playedGameData['ticket']=$ticket;

					$currentGame->game_status="WAITING";
					$currentGame->save();

					$playedGame=PlayedGame::create($playedGameData);

				DB::commit();

				$this->sendCurrentGame();

			}catch(Exception $e){
				DB::rollBack();
			}
			}
		}

		if($gameDateTime<=$nowDateTime){
			if($currentGame->game_status!="STARTED"&&$currentGame->game_status!="COMPLETED"&&$currentGame->game_status!="CANCELLED"){
				$currentGame->game_status="STARTED";
				$currentGame->save();

				// $cacheHistory=CacheHistory::first();
				// $cacheHistory->current_game_cn=$cacheHistory->current_game_cn+1;
				// $cacheHistory->save();

				$this->sendCurrentGame();

				$ch = curl_init('https://maxtambola.com/api/call_number/HSU82347HSBQ344HH');                                                                      
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                                                                                      
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
																								
				curl_exec($ch)."\n";
				curl_close($ch);
			}
		}
		

		if((!$currentGame||$currentGame->game_datetime<$now)&&$currentGame->game_status!="WAITING"&&$currentGame->game_status!="STARTED"){
			$game=Game::where('game_datetime','>=',$now)
					->orderBy('game_datetime','ASC')
					->first();
		
		if(!$game){
			// $this->sendCurrentGame();
			return response()->json(['status'=>'SUCCESS','code'=>'FC_02','data'=>null]);
		}

		if(!$currentGame){
			$currentGame=new CurrentGame();
		}

		$gameCarbon=new Carbon($game->game_datetime);
		$gameCarbon->subMinutes($game->booking_close_minutes);
		$bookingCloseTime=$gameCarbon->toDateTimeString();

		$currentGame->game_date=$game->game_date;
		$currentGame->game_time=$game->game_time;
		$currentGame->ticket_price=$game->ticket_price;
		if($game->type=="BUMPER")
			$currentGame->bumper=1;
		else $currentGame->bumper=0;
		$currentGame->game_datetime=$game->game_datetime;
		$currentGame->ticket_price=$game->ticket_price;
		$currentGame->game_status="NEW";
		$currentGame->booking_close_time=$bookingCloseTime;

		$currentGame->save();

		// $cacheHistory=CacheHistory::first();
		// $cacheHistory->current_game_cn=$cacheHistory->current_game_cn+1;
		// $cacheHistory->normal_game_cn=$cacheHistory->normal_game_cn+1;
		// $cacheHistory->bumper_game_cn=$cacheHistory->bumper_game_cn+1;
		// $cacheHistory->save();
	
		$currentGame=$currentGame->fresh();

		$this->sendCurrentGame();

		if($currentGame->bumper==1){
			BumperPrize::orderBy('id','ASC')->update(['prize_status'=>"NEW"]);
		}else{
			Prize::orderBy('id','ASC')->update(['prize_status'=>"NEW"]);
		}

	}
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);

	}

	public function processMessage($message,$gameDateTime,$templateId,$type){
		$users=GameTicket::join('users','users.id','game_tickets.user_id')
							->where('game_tickets.game_datetime',$gameDateTime)
							->select('users.phone','users.token');

		$finalPhones=[];
		$finalTokens=[];

		foreach($users as $user){
			if(!in_array($user['phone'],$finalPhones)){
				array_push($finalPhones,$user['phone']);
			}

			if(!in_array($user['token'],$finalTokens)){
				array_push($finalTokens,$user['token']);
			}
		}

		$setting=Setting::first();
		$auth=$setting->gcm_auth;

		$numbers="";

		foreach($finalPhones as $phone){
			$numbers=$numbers."91".$phone.',';
		}

		$numbers=substr($numbers,0,-1);
		
		$this->sendMessage($message,$numbers,$templateId,$type);

		$notification=$this->notification($finalTokens,"Game Update",$message,$auth);

		return null;
	}

	public function sendMessage($message,$numbers,$templateId,$type){
		$otpData=[];
        $otpData['api_id']="APITsXpPNWb29866";
        $otpData['api_password']="maxtambolA1";
        $otpData['sms_type']=$type;
        $otpData['sms_encoding']="1";
        $otpData['sender']="LUDOPD";
        $otpData['message']=$message;
        $otpData['number']=$numbers;
        $otpData['template_id']=$templateId;

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

	//31. Send Notification
	public function send_notification(Request $request){
		$title=$request->notification_title;
		$message=$request->notification_message;
		$sendTo=$request->send_to;

		$data=[];
		$data['notification_title']=$title;
		$data['notification_message']=$message;
		$data['send_to']=$sendTo;

		$notification=$this->process_notifications($data);

		if($notification){
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
		}else{
			return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
		}
	}

	public function process_notifications($requestData){
		$title=$requestData['notification_title'];
		$message=$requestData['notification_message'];
		$sendTo=$requestData['send_to'];

		$setting=Setting::first();
		$auth=$setting->gcm_auth;

		$tokens=[];
		if($sendTo=="USERS"){
			$tokens=User::where('is_blocked',0)
						->where('token','!=',null)
						->pluck('token');
		}else if($sendTo=="TICKETS"){
			$currentGame=CurrentGame::first();
			$gameDate=$currentGame->game_date;
			$gameTime=$currentGame->game_time;

			$userIds=User::where('game_date',$gameDate)
						->where('game_time',$gameTime)
						->pluck('user_id');

			$tokens=User::whereIn('id',$userIds)
						->where('token','!=',null)
						->pluck('token');
		}else{
			$currentGame=CurrentGame::first();
			$gameDate=$currentGame->game_date;
			$gameTime=$currentGame->game_time;

			$userIds=User::where('game_date',$gameDate)
						->where('game_time',$gameTime)
						->pluck('user_id');

			$tokens=User::whereNotIn('id',$userIds)
						->where('token','!=',null)
						->pluck('token');
		}


		$finalTokens=[];
		foreach($tokens as $token){
			if(!in_array($token,$finalTokens)){
				array_push($finalTokens,$token);
			}
		}

		$notification=$this->notification($finalTokens,$title,$message,$auth);

		return $notification;
	}

	//32. Send SMS
	public function send_sms(){

	}

	//33. Get All Tickets
	public function get_all_tickets(){
		$tickets=Ticket::orderBy('ticket_number')
						->get();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$tickets]);
	}


	//34. Get All Bumper Tickets
	public function get_bumper_tickets(){
		$tickets=BumperTicket::orderBy('ticket_number')
						->get();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$tickets]);
	}


	//35. Randomize Bumper Tickets
	public function randomize_bumper_tickets(){

		DB::beginTransaction();

		try{
			$generateTickets=new GenerateTicket();

			$allTickets=[];
			$times=0;
			do{
				$allTickets=[];
				$times++;
				for($i=0;$i<100;$i++){
					$tickets=$generateTickets->generate_tickets();
					$allTickets=array_merge($allTickets,$tickets);
				}
				if($times>2){
					return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
				}
			}while(count(array_unique($allTickets))!=600);
	
			$finalData=[];
			for($i=0;$i<count($allTickets);$i++){
				$data=[];
				$data['ticket']=$allTickets[$i];
				$data['ticket_number']=$i+1;
				// $data['customer_name']="";
				// $data['customer_profile']="";
				// $data['ticket_status']="AVAILABLE";
	
				array_push($finalData,$data);
			}
	
			BumperTicket::truncate();
			BumperTicket::insert($finalData);
	
			$cacheHistory=CacheHistory::first();
			$cacheHistory->bumper_ticket_change_cn+=1;
			$cacheHistory->save();
	
			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'FAILED','code'=>'FC_01','data'=>null]);
		}
	}


	//36. Randomize Bumper Sequence
	public function randomize_bumper_sequence(){

		DB::beginTransaction();

		try{
			$tickets=BumperTicket::get();

			$allTickets=[];
			foreach($tickets as $ticket){
				array_push($allTickets,$ticket->ticket);
			}
	
			shuffle($allTickets);
	
			$finalData=[];
			for($i=0;$i<count($allTickets);$i++){
				$data=[];
				$data['ticket']=$allTickets[$i];
				$data['ticket_number']=$i+1;
				// $data['customer_name']="";
				// $data['customer_profile']="";
				// $data['ticket_status']="AVAILABLE";
	
				array_push($finalData,$data);
			}
	
			BumperTicket::truncate();
			BumperTicket::insert($finalData);
	
			$cacheHistory=CacheHistory::first();
			$cacheHistory->bumper_ticket_change_cn+=1;
			$cacheHistory->save();


			DB::commit();
	
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'FAILED','code'=>'FC_01','data'=>null]);
		}
	}



	//37. Get All Notifications
	public function get_all_notifications(){
		$notifications=Notification::get();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$notifications]);
	}


	//38. Cancel tickets by user
	public function cancel_tickets_user(Request $request){
		$gameCarbon=Carbon::createFromFormat('d-m-Y H:i:s',$request->game_date.' '.$request->game_time,'Asia/Calcutta');
		$gameCarbon->setTimezone('UTC');

		$gameDate=$gameCarbon->toDateString();
		$gameTime=$gameCarbon->toTimeString();
		$userId=$request->user_id;


		DB::beginTransaction();

		try{
		$gameTickets=GameTicket::where('game_date',$gameDate)
							->where('game_time',$gameTime)
							->where('user_id',$userId)
							->get()
							->groupBy('transaction_id');

		$transactionCount=Transaction::count()+1;
		$txnId="TXNID".$transactionCount;
		$orderId="ORDERID".$transactionCount;
		$user=User::where('id',$userId)->first();
		
		foreach($gameTickets as $gt){
			$totalAmount=0;
			$ticketCount=count($gt);
			$userId=-1;
			$transactionId="";		
			foreach($gt as $gtt){
				$totalAmount+=$gtt['ticket_price'];
				$userId=$gtt['user_id'];
				$transactionId=$gtt['transaction_id'];
			}

			$admin=Admin::first();
			$adminBalance=$admin->wallet_balance;
			$walletBalance=$user->wallet_balance;
			$updatedBalance=$walletBalance+$totalAmount;

			$transaction=Transaction::where('txn_id',$transactionId)
									->where('user_id',$userId)
									->update(['txn_message'=>"Game for date ".$gameDate." and time ".$gameTime." cancelled and refunded to wallet.",
												'closing_balance'=>$updatedBalance,'txn_title'=>"Refund for Cancelled Game",'txn_admin_title'=>"Amount refunded due to game cancellation",'txn_sub_title'=>$ticketCount." Ticket Refunded Successfully"]);

				$user->wallet_balance=$updatedBalance;
				$admin->wallet_balance=$adminBalance-$totalAmount;
				$admin->save();
				$user->save();
		}

		$balanceData=[];
		$balanceData['user_id']=$user->id;
		$balanceData['wallet_balance']=$user->wallet_balance;
		$balanceData['locked_balance']=$user->locked_balance;
	
		$this->sendWalletBalance($balanceData);

		$gameTickets=GameTicket::where('game_date',$gameDate)
							->where('game_time',$gameTime)
							->get();

		$now=new Carbon();
		$bumperGame=Game::where('game_datetime','>=',$now)
							->where('type','BUMPER')
							->orderBy('game_datetime','DESC')
							->first();
		
		$normalGame=Game::where('game_datetime','>=',$now)
							->where('type','NORMAL')
							->orderBy('game_datetime','DESC')
							->first();

		if($bumperGame&&$bumperGame->game_date==$gameDate&&$bumperGame->game_time==$gameTime){
			foreach($gameTickets as $gt){
					GameTicket::where('id',$gt->id)->delete();
			}

			// $cacheHistory=CacheHistory::first();
			// $cacheHistory->bumper_game_ticket_cn=$cacheHistory->bumper_game_ticket_cn+1;
			// $cacheHistory->save();

		}else if($normalGame&&$normalGame->game_date==$gameDate&&$normalGame->game_time==$gameTime){
			foreach($gameTickets as $gt){
					GameTicket::where('id',$gt->id)->delete();
			}

			// $cacheHistory=CacheHistory::first();
			// $cacheHistory->normal_game_ticket_cn=$cacheHistory->normal_game_ticket_cn+1;
			// $cacheHistory->save();
		}					

		DB::commit();
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);	
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);	
		}
	}


	//39. Reschedule tickets by user
	public function reschedule_tickets_user(Request $request){
		$gameCarbon=Carbon::createFromFormat('d-m-Y H:i:s',$request->game_date.' '.$request->game_time,'Asia/Calcutta');
		$gameCarbon->setTimezone('UTC');

		$gameDate=$gameCarbon->toDateString();
		$gameTime=$gameCarbon->toTimeString();
		$userId=$request->user_id;

		$newGameCarbon=Carbon::createFromFormat('d-m-Y H:i:s',$request->date.' '.$request->time,'Asia/Calcutta');
		$newGameCarbon->setTimezone('UTC');

		$date=$newGameCarbon->toDateString();
		$time=$newGameCarbon->toTimeString();

		$gameDateTime=$newGameCarbon->toDateTimeString();

		DB::beginTransaction();
		try{
			$gameTickets=GameTicket::where('game_date',$gameDate)
						->where('game_time',$gameTime)
						->where('user_id',$userId)
						->update(['game_date'=>$date,'game_time'=>$time,'game_datetime'=>$gameDateTime]);

			// $cacheHistory=CacheHistory::first();
			// $cacheHistory->normal_game_ticket_cn=$cacheHistory->normal_game_ticket_cn+1;
			// $cacheHistory->bumper_game_ticket_cn=$cacheHistory->bumper_game_ticket_cn+1;
			// $cacheHistory->save();

			DB::commit();
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$gameTickets]);		
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'FAILED','code'=>'FC_01','data'=>null]);		
		}
	}

	//40. Update Notification Status
	public function update_notification_status(Request $request){
		$notificationCarbon=Carbon::createFromFormat('H:i:s',$request->notification_time,'Asia/Calcutta');
		$notificationCarbon->setTimezone('UTC');

		$notificationTime=$notificationCarbon->toTimeString();

		$notification=Notification::where('notification_time',$notificationTime)
								->first();

		if($notification->status==0){
			$notification->status=1;
		}else{
			$notification->status=0;
		}

		$notification->save();
		$notification->fresh();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$notification]);		
	}


	//41. Update Prize Setting
	public function update_prize_setting(Request $request){
		$type=$request->type;
		$prizeTag=$request->prize_tag;
		$prizeName=$request->prize_name;
		$prizeAmount=$request->prize_amount;


		DB::beginTransaction();
		try{
			if($type=="BUMPER"){
				$bumperPrize=BumperPrize::where('prize_tag',$prizeTag)->first();
	
				$bumperPrize->prize_name=$prizeName;
				$bumperPrize->prize_amount=$prizeAmount;
				$bumperPrize->save();
	
				$cacheHistory=CacheHistory::first();
				$cacheHistory->prize_change_cn+=1;
				$cacheHistory->save();
	
				DB::commit();
				return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$bumperPrize]);		
			}else{
				$prize=Prize::where('prize_tag',$prizeTag)->first();
	
				$prize->prize_name=$prizeName;
				$prize->prize_amount=$prizeAmount;
				$prize->save();
	
				$cacheHistory=CacheHistory::first();
				$cacheHistory->prize_change_cn+=1;
				$cacheHistory->save();
	
				DB::commit();
				return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$prize]);		
			}
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'FAILED','code'=>'FC_01','data'=>null]);		
		}
		
	}


	//42. Update Prize Status
	public function update_prize_status(Request $request){
		$type=$request->type;
		$prizeTag=$request->prize_tag;

		DB::beginTransaction();

		try{
			if($type=="BUMPER"){
				$bumperPrize=BumperPrize::where('prize_tag',$prizeTag)->first();
	
				if($bumperPrize->status==1){
					$bumperPrize->status=0;
				}else{
					$bumperPrize->status=1;
				}
				$bumperPrize->save();
	
				$cacheHistory=CacheHistory::first();
				$cacheHistory->prize_change_cn+=1;
				$cacheHistory->save();
	

				DB::commit();
				return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$bumperPrize]);		
			}else{
				$prize=Prize::where('prize_tag',$prizeTag)->first();
			
				if($prize->status==1){
					$prize->status=0;
				}else{
					$prize->status=1;
				}
				$prize->save();
	
				$cacheHistory=CacheHistory::first();
				$cacheHistory->prize_change_cn+=1;
				$cacheHistory->save();
	
				DB::commit();
				return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$prize]);	
			}
		}catch(Exception $e){
			DB::rollBack();
			return response()->json(['status'=>'FAILED','code'=>'FC_01','data'=>null]);	
		}
	}

	//43. Get Game times for date
	public function get_game_times($date){
		$gameCarbon=Carbon::createFromFormat('d-m-Y H:i:s',$date,'Asia/Calcutta');
		$gameCarbon->setTimezone('UTC');

		$times=GameTicket::where('game_date',$gameCarbon->toDateString())
						->pluck('game_time');

		$finalTimes=[];
		foreach($times as $time){
			if(!in_array($time,$finalTimes)){
				array_push($finalTimes,$time);
			}
		}
			
		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$finalTimes]);	
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


	//44. Get Admin Balance
	public function get_admin_balance(){
		$admin=Admin::select('wallet_balance')->first();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$admin]);	
	}


	//45. Get Game Page Data
	public function game_page_data($date,$time){
		$gameCarbon=Carbon::createFromFormat('d-m-Y H:i:s',$date.' '.$time,'Asia/Calcutta');
		$gameCarbon->setTimezone('UTC');

		$date=$gameCarbon->toDateString();
		$time=$gameCarbon->toTimeString();

		$playedGame=PlayedGame::where('game_date',$date)
							->where('game_time',$time)
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
		
		$prizes=Prize::where('status',1)
					->get();
		$claims=GameClaim::leftjoin('users','users.id','game_claims.user_id')
					->where('game_claims.game_date',$date)
					->where('game_claims.game_time',$time)
					->select('game_claims.*','users.user_name','users.user_profile')
					->get();
		
		$data=[];
		$data['played_game']=$playedGame;
		$data['prizes']=$prizes;
		$data['claims']=$claims;
		$data['player_count']=$playerCount;
		$data['total_tickets']=$ticketCount;

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$data]);
	}


	//46. Get All Pending Transactions
	public function get_pending_txn_export(){
		$transaction=Transaction::where('txn_status','PENDING')
								->where('txn_type','WITHDRAW')
								->orderBy('created_at','DESC')
								->get();		

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);	
	}


	//47. Get default status message
	public function get_deafult_messages($type){
		$messages=DefaultMessage::where('type',$type)
								->get();

		return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$messages]);	
	}

	//48. Restart server
	public function restart_server(){
		$output=null;
		$code=null;
		exec("pm2 start /var/www/html/webtambola/server.js",$output,$code);

		if($output==null){
			return response()->json(['status'=>'FAILURE','code'=>'FC_01','message'=>"Failed to start server"]);	
		}else{
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','message'=>"Successfully restarted the server"]);	
		}
	}

	public function create_default_message(Request $request){
		$id=$request->id;

		if($id!=-1){
			$message=DefaultMessage::where('id',$id)
								->first();
			if($message){
				$message->message=$request->message;
				$message->save();
			}else{
				$messageData['message']=$request->message;
				$messageData['type']=$request->type;

				$message=DefaultMessage::create($messageData);
			}
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$message]);
		}else{
			$message=DefaultMessage::where('type',$request->type)
								->where('message',$request->message)
								->first();
			if(!$message){
				$messageData['message']=$request->message;
				$messageData['type']=$request->type;

				$message=DefaultMessage::create($messageData);
			}
			return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$message]);
		}
	}
	
}
