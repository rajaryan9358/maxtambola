<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CashFreeController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('get_metadata',[UserController::class,'get_metadata']);
Route::get('get_sold_gametime_tickets/{game_datetime}',[UserController::class,'get_sold_gametime_tickets']);
Route::get('get_all_games',[UserController::class,'get_all_games']);
Route::get('get_all_prizes',[UserController::class,'get_all_prizes']);

Route::get('get_verison_detail',[UserController::class,'get_verison_detail']);

Route::post('update_payu_payment',[UserController::class,'update_payu_payment']);
Route::post('get_payu_payload',[UserController::class,'get_payu_payload']);
Route::post('initiate_payu_payment',[UserController::class,'initiate_payu_payment']);


Route::get('call_number/{key}',[GameController::class,'call_number']);
Route::get('set_default_settings',[AdminController::class,'set_default_settings']);

Route::post('test_video_upload',[UserController::class,'test_video_upload']);

Route::get('get_homepage_data/{userId}',[UserController::class,'get_homepage_data']);
//User Controller
Route::post('send_otp',[UserController::class,'send_otp']);
Route::post('verify_otp',[UserController::class,'verify_otp']);
Route::post('register_user',[UserController::class,'register_user']);
Route::post('update_token',[UserController::class,'update_token']);
Route::post('update_profile',[UserController::class,'update_profile']);
Route::get('get_my_tickets/{userId}',[UserController::class,'get_my_tickets']);
Route::get('get_user_profile/{userId}',[UserController::class,'get_user_profile']);
Route::get('get_next_game_time',[UserController::class,'get_next_game_time']);
Route::get('get_prizes/{type}',[UserController::class,'get_prizes']);
Route::get('get_leaderboard/{type}',[UserController::class,'get_leaderboard']);
Route::get('get_all_tickets',[UserController::class,'get_all_tickets']);
Route::post('purchase_ticket',[UserController::class,'purchase_ticket']);
Route::post('add_transaction',[UserController::class,'add_transaction']);
Route::get('get_bumper_ticket',[UserController::class,'get_bumper_ticket']);
Route::post('get_my_transactions',[UserController::class,'get_my_transactions']);
Route::get('get_game_data/{userId}',[UserController::class,'get_game_data']);
Route::get('join_game/{userId}',[UserController::class,'join_game']);
Route::post('claim_prize',[UserController::class,'claim_prize']);
Route::get('get_bumper_game',[UserController::class,'get_bumper_game']);
Route::post('update_transaction',[UserController::class,'update_transaction']);
Route::post('save_bank',[UserController::class,'save_bank']);
Route::get('get_bank/{userId}',[UserController::class,'get_bank']);
Route::post('submit_kyc_request',[UserController::class,'submit_kyc_request']);
Route::get('get_upcoming_tickets/{userId}',[UserController::class,'get_upcoming_tickets']);
Route::post('paytm_token',[UserController::class,'paytm_token']);
Route::get('get_user_setting',[UserController::class,'get_user_setting']);
Route::get('get_info/{tag}',[UserController::class,'get_info']);
Route::get('get_my_ticket_numbers/{userId}/{type}',[UserController::class,'get_my_ticket_numbers']);
Route::get('get_transaction_by_id/{txnId}',[UserController::class,'get_transaction_by_id']);
Route::get('get_my_kyc/{userId}',[UserController::class,'get_my_kyc']);
Route::post('update_paytm_transaction',[UserController::class,'update_paytm_transaction']);
Route::get('check_add_money_status/{orderId}',[UserController::class,'check_add_money_status']);
Route::get('get_homepage_details/{userId}',[UserController::class,'get_homepage_details']);
Route::post('submit_withdraw_request_admin',[UserController::class,'submit_withdraw_request_admin']);
Route::post('update_withdrawal_request_admin',[UserController::class,'update_withdrawal_request_admin']);
Route::get('get_transaction_payout/{txnId}',[UserController::class,'get_transaction_payout']);



//Admin Controller
Route::post('login_admin',[AdminController::class,'login_admin']);
Route::get('get_next_game_details',[AdminController::class,'get_next_game_details']);
Route::post('get_pending_payments',[AdminController::class,'get_pending_payments']);
Route::get('get_active_game',[AdminController::class,'get_active_game']);
Route::post('add_game',[AdminController::class,'add_game']);
Route::post('delete_game',[AdminController::class,'delete_game']);
Route::post('update_game',[AdminController::class,'update_game']);
Route::post('get_transactions',[AdminController::class,'get_transactions']);
Route::post('get_all_users',[AdminController::class,'get_all_users']);
Route::get('block_unblock_user/{userId}',[AdminController::class,'block_unblock_user']);
Route::post('add_user_transaction',[AdminController::class,'add_user_transaction']);
Route::get('get_sold_tickets/{date}/{time}',[AdminController::class,'get_sold_tickets']);
Route::get('get_all_tickets_by_user/{date}/{time}/{userId}',[AdminController::class,'get_all_tickets_by_user']);
Route::post('get_users_kyc',[AdminController::class,'get_users_kyc']);
Route::get('get_prize_settings/{type}',[AdminController::class,'get_prize_settings']);
Route::post('add_notification',[AdminController::class,'add_notification']);
Route::post('update_notification',[AdminController::class,'update_notification']);
Route::get('delete_notification/{notificationId}',[AdminController::class,'delete_notification']);
Route::post('cancel_game_refund',[AdminController::class,'cancel_game_refund']);
Route::post('cancel_game_reschedule',[AdminController::class,'cancel_game_reschedule']);
Route::post('get_user_transactions',[AdminController::class,'get_user_transactions']);
Route::post('get_user_tickets',[AdminController::class,'get_user_tickets']);
Route::get('get_setting',[AdminController::class,'get_setting']);
Route::post('update_setting',[AdminController::class,'update_setting']);
Route::get('get_past_game/{gameDate}/{gameTime}',[AdminController::class,'get_past_game']);
Route::post('update_kyc_status',[AdminController::class,'update_kyc_status']);
Route::get('randomize_tickets',[AdminController::class,'randomize_tickets']);
Route::get('randomize_sequence',[AdminController::class,'randomize_sequence']);
Route::get('check_game_status',[AdminController::class,'check_game_status']);
Route::get('get_bumper_tickets',[AdminController::class,'get_bumper_tickets']);
Route::get('randomize_bumper_tickets',[AdminController::class,'randomize_bumper_tickets']);
Route::get('randomize_bumper_sequence',[AdminController::class,'randomize_bumper_sequence']);
Route::get('get_all_notifications',[AdminController::class,'get_all_notifications']);
Route::post('cancel_tickets_user',[AdminController::class,'cancel_tickets_user']);
Route::post('reschedule_tickets_user',[AdminController::class,'reschedule_tickets_user']);
Route::post('update_notification_status',[AdminController::class,'update_notification_status']);
Route::post('update_prize_setting',[AdminController::class,'update_prize_setting']);
Route::post('update_prize_status',[AdminController::class,'update_prize_status']);
Route::get('get_game_times/{date}',[AdminController::class,'get_game_times']);
Route::post('send_notification',[AdminController::class,'send_notification']);
Route::get('get_admin_balance',[AdminController::class,'get_admin_balance']);
Route::get('game_page_data/{date}/{time}',[AdminController::class,'game_page_data']);
Route::get('get_pending_txn_export',[AdminController::class,'get_pending_txn_export']);
Route::get('get_deafult_messages/{type}',[AdminController::class,'get_deafult_messages']);
Route::get('restart_server',[AdminController::class,'restart_server']);
Route::post('create_default_message',[AdminController::class,'create_default_message']);


//CashFree Controller
Route::post('create_cashfree_token',[CashFreeController::class,'create_cashfree_token']);
Route::post('cashfree_webhook',[CashFreeController::class,'cashfree_webhook']);
Route::post('verify_cashfree_signature',[CashFreeController::class,'verify_cashfree_signature']);
Route::post('add_cfp_beneficiary',[CashFreeController::class,'add_cfp_beneficiary']);
Route::get('remove_beneficiary/{beneId}',[CashFreeController::class,'remove_beneficiary']);
Route::post('request_cashfree_transfer',[CashFreeController::class,'request_cashfree_transfer']);
Route::post('get_cfp_transfer_status',[CashFreeController::class,'get_cfp_transfer_status']);
Route::post('cfp_transfer_webhook',[CashFreeController::class,'cfp_transfer_webhook']);

//User Api (UPI Payment)
//1. create UPI order
//2. update UPI status

//User Api (Manual Payment)
//1. submit payment request
//2. cancel payment request

//User Api (Withdrawal Request)
//1. submit withdrawal request
//2. cancel withdrawal request

Route::post('create_upi_order',[UserController::class,'create_upi_order']);
Route::post('update_payment_status',[UserController::class,'update_payment_status']);
Route::post('submit_payment_request',[UserController::class,'submit_payment_request']);
Route::post('cancel_payment_request',[UserController::class,'cancel_payment_request']);
Route::post('submit_withdraw_request',[UserController::class,'submit_withdraw_request']);
Route::post('cancel_withdrawal_request',[UserController::class,'cancel_withdrawal_request']);
Route::get('get_payment_setting',[UserController::class,'get_payment_setting']);

//Admin Api (Transaction status)
//1. Change transaction status

Route::post('change_transaction_status',[AdminController::class,'change_transaction_status']);
Route::post('update_payment_setting',[AdminController::class,'update_payment_setting']);
