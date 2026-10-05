<?php
namespace App\Controllers;
use App\Core\{Controller,Auth};
use App\Services\AuthService;
final class AuthController extends Controller {
 public function loginForm():void {$this->view('auth/form',['register'=>false],'Đăng nhập');}
 public function registerForm():void {$this->view('auth/form',['register'=>true],'Tạo tài khoản');}
 public function login():void {(new AuthService())->login($this->request->post());$this->success('Chào mừng bạn trở lại!','/account');}
 public function register():void {(new AuthService())->register($this->request->post());$this->success('Tạo tài khoản thành công.','/account');}
 public function logout():void {Auth::logout();$this->success('Bạn đã đăng xuất.');}
}
