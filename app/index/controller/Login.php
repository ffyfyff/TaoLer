<?php
namespace app\index\Controller;

use app\common\controller\BaseController;
use app\common\validate\User as userValidate;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\Cookie;
use think\facade\Cache;
use think\facade\View;
use app\common\model\User as userModel;

class Login extends BaseController
{
	//已登陆检测
	protected $middleware = ['logedcheck'];

    //用户登陆
	public function index()
	{
		//var_dump($_SERVER);
        if(Request::isAjax()) {
			$data = Request::param();

			//邮箱正则表达式
			$pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
			//判断输入的是邮箱还是用户名
		   if (preg_match($pattern, $data['name'])){ 
               $data['email'] = $data['name'];
			   unset($data['name']);
				//输入邮箱email登陆验证
			   try{
                    validate(userValidate::class)
                        ->scene('loginEmail')
                        ->check($data);
                } catch (ValidateException $e) {
                    // 验证失败 输出错误信息
                    return json(['code'=>-1,'msg'=>$e->getError()]);
                }
                $data['name'] = $data['email'];
				unset($data['email']);
		   } else {
			   //用户名name登陆验证
			   try{
                    validate(userValidate::class)
                        ->scene('loginName')
                        ->check($data);
                } catch (ValidateException $e) {
                    // 验证失败 输出错误信息
					return json(['code'=>-1,'msg'=>$e->getError()]);
                }  
		   }			
			//登陆请求
			$user = new \app\common\model\User();
			$res = $user->login($data);
            if ($res == 1) {
                return json(['code'=>0,'msg'=>'登陆成功','url'=>'/']);
            } else {
				return json(['code'=>-1,'msg'=>$res]);
            }
        }
        return View::fetch('login');
	}

    //注册
    public function reg()
    {
        if(Request::isAjax()){
			$data = Request::only(['name','email','password','repassword','captcha']);
		
        //校验场景中reg的方法数据
		try{
            validate(userValidate::class)
                ->scene('Reg')
                ->check($data);
        } catch (ValidateException $e) {
				return json(['code'=>-1,'msg'=>$e->getError()]);
        }
		
		$user = new userModel;
		$result = $user->reg($data);
		
           if ($result == 1) {
			   $res = ['code'=>0,'msg'=>'注册成功','url'=>'/index/login'];
           }else {
			   $res = ['code'=>-1,'msg'=>$result];
           }
		   return json($res);
        }
        //加载注册模板
        return View::fetch();
    }
	
	//找回密码
	public function forget()
	{
		if(Request::isAjax()){
			$data = Request::param();
			
		try{
            validate(userValidate::class)
                ->scene('Forget')
                ->check($data);
        } catch (ValidateException $e) {
			return json(['code'=>-1,'msg'=>$e->getError()]);
        }
		//查询用户
		$user = Db::name('user')->where('email',$data['email'])->find();
            if($user) {
                $code = mt_rand('1111','9999');
                Cache::set('code',$code,600);
                Cache::set('userid',$user['id'],600);

                $result = mailto($data['email'],'重置密码','Hi亲爱的'.$user['name'].':</br>您正在维护您的信息，请在10分钟内验证，您的验证码为:'.$code);
                if($result){
                    Cache::set('repass',1,60);	//设置repass标志为1存入Cache
					$res = ['code'=>0,'msg'=>'验证码已发送成功，请去邮箱查看！','url'=>'/index/postcode']; 
                } else {
                    $res = ['code'=>-1,'msg'=>'验证码发送失败!'];
                }	
            }else{
                $res = ['code' =>-1,'msg'=>'邮箱错误或不存在'];
            }
			return json($res);
		}
		return View::fetch();
	}
	
	//接收验证码
	public function postcode()
	{
        if(Cache::get('repass') != 1){
			return redirect('/index/forget');
        }
        if(Request::isAjax()){
		$code = Request::only(['code']);
		try{
            validate(userValidate::class)
                        ->scene('Code')
                        ->check($code);
        } catch (ValidateException $e) {
            return json(['code'=>-1,'msg'=>$e->getError()]);
        }

		    if(Cache::get('code')==$code['code']) { //无任何输入情况下需排除code为0和Cache为0的情况
                //Cache::delete('repass');
                Cache::set('repass',2,60);
				$res = ['code'=>0,'msg'=>'验证成功','url'=>'/index/respass'];
		    } else {
			    $res = ['code'=>-1,'msg'=>'验证码错误或已过期！'];
		    }
			return json($res);
        }
		return View::fetch('forget');
	}
	
	//忘记密码找回重置
	public function respass()
	{
        if(Cache::get('repass') != 2){
            return redirect('/index/forget');
        }
        if(Request::isAjax()){
            $data = Request::param();
			try{
				validate(userValidate::class)
							->scene('Repass')
							->check($data);
			} catch (ValidateException $e) {
				return json(['code'=>-1,'msg'=>$e->getError()]);
			}	
			
			$data['uid'] = Cache::get('userid');
			$user = new \app\common\model\User();
			$res = $user->respass($data);
				if ($res == 1) {
						return json(['code'=>0,'msg'=>'修改成功','url'=>'/index/login']);
				} else {
						return json(['code'=>-1,'msg'=>'$res']);		
				}
        }
		return View::fetch('forget');
	}

}