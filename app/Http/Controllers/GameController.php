<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\BumperPrize;
use App\Models\CurrentGame;
use App\Models\GameClaim;
use App\Models\PlayedGame;
use App\Models\Prize;
use App\Models\Setting;
use App\Models\Transaction;
use Carbon\Carbon;
use App\Models\CacheHistory;
use App\Models\Game;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    
    public function call_number($key){

        $isGameCompleted=false;

        if($key!="HSU82347HSBQ344HH"){
            return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
        }

        DB::beginTransaction();
        try{
            
        $currentGame=CurrentGame::first();
        $setting=Setting::first();

        if($currentGame->game_status=="STARTED"){
            $playedGame=PlayedGame::where('game_date',$currentGame->game_date)
                                ->where('game_time',$currentGame->game_time)
                                ->first();

            $socketData=[];
            $socketData['duration']=$setting->call_duration;
            $socketData['status']=$currentGame->game_status;
            
            if($playedGame){
                $allNumbers=$playedGame->ticket;
                $calledNumbers=$playedGame->called_numbers;
                $allNumberArray=explode(",",$allNumbers);
                $calledNumberArray=explode(",",$calledNumbers);
                $lona=array_diff($allNumberArray,$calledNumberArray);
                $leftOverNumberArray=[];

                foreach($lona as $lon){
                    array_push($leftOverNumberArray,$lon);
                }

                if($currentGame->bumper==1){
                    $prizes=BumperPrize::where('status',1)
                                ->where('prize_status','!=',"CLAIMED")
                                ->get();
                }else{
                    $prizes=Prize::where('status',1)
                            ->where('prize_status','!=',"CLAIMED")
                            ->get();
                }
                
                    if(count($prizes)==0){
                        //All Prizes claimed finish game 
                        $currentGame->game_status="COMPLETED";
                        $currentGame->save();
                        $socketData['status']="COMPLETED";

                        $isGameCompleted=true;
                    }

                    $claimingPrizes=[];
                    foreach($prizes as $prize){
                        if($prize->prize_status=="CLAIMING"){
                            array_push($claimingPrizes,$prize);
                        }
                    }

                    $overAllWinnerCount=0;

                    $newPrizeClaims=[];
                    foreach($claimingPrizes as $claimingPrize){
                        $gameClaims=GameClaim::where('game_date',$currentGame->game_date)
                                            ->where('game_time',$currentGame->game_time)
                                            ->where('prize_tag',$claimingPrize['prize_tag'])
                                            ->get();

                        if(count($gameClaims)>0){

                        }
                        array_push($newPrizeClaims,$claimingPrize['prize_tag']);
                        
                        $prizeAmount=$claimingPrize['prize_amount'];
                        $totalWinners=count($gameClaims);
                        $overAllWinnerCount=$totalWinners;

                        if($totalWinners>0){
                            $singleWinAmount=floor($prizeAmount/$totalWinners);

                            foreach($gameClaims as $gameClaim){
                                $transactionCount=Transaction::count()+1;
                                $txnId="TXNID".$transactionCount;
                                $orderId="ORDERID".$transactionCount;
    
                                $gcc=GameClaim::where('id',$gameClaim['id'])->first();
    
                                if($gcc){
                                    $gcc->prize_amount=$singleWinAmount;
                                    $gcc->save();
                                }
    
                                $user=User::where('id',$gameClaim['user_id'])
                                            ->first();
                                $admin=Admin::first();
                                $userBalance=$user->wallet_balance;
                                $updatedBalance=$userBalance+$singleWinAmount;
                                $adminBalance=$admin->wallet_balance;
    
    
                                $transactionData=[];
                                $transactionData['order_id']=$orderId;
                                $transactionData['txn_id']=$txnId;
                                $transactionData['user_id']=$gameClaim->user_id;
                                $transactionData['txn_mode']="WALLET";
                                $transactionData['txn_type']="CLAIM";
                                $transactionData['txn_status']="SUCCESS";
                                $transactionData['txn_message']=$gameClaim->prize_name." claimed for Game on ".$currentGame->game_datetime." at ".$gameClaim->created_at;
                                $transactionData['reference_id']="-";
                                $transactionData['account_number']="-";
                                $transactionData['account_name']="-";
                                $transactionData['account_ifsc']="-";
                                $transactionData['txn_amount']=$singleWinAmount;
                                $transactionData['closing_balance']=$updatedBalance;
                                $transactionData['created_at']=now();
                                $transactionData['updated_at']=now();
                                $transactionData['txn_title']=$gameClaim->prize_name." Claimed for ".$currentGame->game_datetime;
                                $transactionData['txn_admin_title']="Paid for Game ".$currentGame->game_datetime."(".$gameClaim->prize_name.")";
                                $transactionData['txn_sub_title']=$gameClaim->prize_name." successfully claimed and added to wallet";
                            
                                $user->wallet_balance=$updatedBalance;
                                $admin->wallet_balance=$adminBalance+$singleWinAmount;
                                $user->save();
                                $admin->save();
                                $transaction=Transaction::create($transactionData);


                                $balanceData=[];
                                $balanceData['user_id']=$user->id;
                                $balanceData['wallet_balance']=$user->wallet_balance;
                                $balanceData['locked_balance']=$user->locked_balance;

                                $this->sendWalletBalance($balanceData);
                            }
                        }

                        
                    }

                    if($overAllWinnerCount>0){
                        foreach($prizes as $prize){
                            if($prize->prize_status=="CLAIMING"){
                                if($currentGame->bumper==1){
                                    $prizeSingle=BumperPrize::where('prize_tag',$prize['prize_tag'])->first();
                                }else{
                                    $prizeSingle=Prize::where('prize_tag',$prize['prize_tag'])->first();
                                }
                                $prizeSingle->prize_status="CLAIMED";
                                $prizeSingle->save();
                            }
                        }
                    }
                

                if(count($leftOverNumberArray)>0){
                    $selectedIndex=array_rand($leftOverNumberArray,1);
                    $selectedNumber=$leftOverNumberArray[$selectedIndex];
                    $playedGame->called_numbers=$calledNumbers.$selectedNumber.",";
                    $playedGame->save();

                    //Send data to node js

                    $socketData['selected_number']=$selectedNumber;
                    $socketData['called_numbers']=$calledNumbers.$selectedNumber.",";
                    $socketData['prizes']=$newPrizeClaims;

                }else{
                    $currentGame->game_status="COMPLETED";
                    $currentGame->save();
                    $socketData['status']="COMPLETED";

                    $isGameCompleted=true;
                }
            }


            $data_string = json_encode($socketData);

			$ch = curl_init('https://maxtambola.com:8080/sendNumber');
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

        DB::commit();

        if($isGameCompleted){
            $this->refresh_game();
            Log::channel('testlog')->info("GAME Completed status ".$isGameCompleted);
        }


        return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>null]);
    }catch(Exception $e){
        DB::rollBack();
        return response()->json(['status'=>'SUCCESS','code'=>'FC_01','data'=>null]);
    }
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

        $this->sendCurrentGame();

        return null;
    }
}
