<?php
declare(strict_types = 1);
namespace app\controller;
use app\model\User;
use Firebase\JWT\JWT;
use think\facade\Env;
use think\facade\Cache;
use app\Request;

class Login
{
    /**
     * 登录
     * @param Request $request
     * @return false|string|\think\Response     返回用户的信息
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function login(Request $request)
    {
        $arr=input();
        $phone=$arr['phone'] ?? null;
        $token=$arr['token'] ?? null;
        $code=$arr['code'] ?? null;
        if(!$phone || !$token || !$code){
            return response('Internal Server Error',500);
        }
        $code1=Cache::store('redis')->get($phone);
        //判断验证码
        if($code!==$code1){
            return response('Internal Server Error',500);
        }
        //验证token
        $res=$this->checkToken($token,$phone);
        if($res){
            $arr=[
                'msg'=>'ok',
                'code'=>200,
                'user_id'=>$res['user_id'],
                'account_num'=>$res['account_num']
            ];
            return json_encode($arr);
        }
        return response('Internal Server Error',500);
    }

    /*
     * 验证token
     * param    token   登录token
     * prarm    phone   手机号
     */
    /**
     * @param string $token     传递的token
     * @param string $phone     用户手机号
     * @return array|bool|\think\Response       成功返回用户信息，失败false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function checkToken(string $token,string $phone){
        $key=Env::get('jwt.jwt_key', '');
        $res=JWT::decode($token,$key,["HS256"]);
        if(!$res){
            return response('Internal Server Error',500);
        }
        $uid=$res->uid;
        $user=new User;
        $info=$user->find($uid);
        //验证token成功，返回用户信息
        if($info['account_num']==$phone){
            return $info->toArray();
        }
        return false;
    }

    /**
     * @param Request $request
     * @return false|string|\think\Response     返回注册成功之后的token值
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function registerDo(Request $request){
        $phone=$request->post('phone');
        $code=$request->post('code');
        if(!$phone || !$code){
            return response('Internal Server Error',500);
        }
        //缓存中取出验证码并验证
        $code1=Cache::store('redis')->get($phone);
        if($code!==$code1){
            return response('Internal Server Error',500);
        }
        //验证通过，注册用户
        $token=$this->addUser($phone);
        Cache::store('redis')->set($phone,null);
        return $token;
    }


    /**
     * 添加用户
     * @param string $phone     用户手机号
     * @return false|string|\think\Response     返回添加之后生成的token
     */
    private function addUser(string $phone){
        $user=new User();
        $uid=$user->insertGetId(['account_num'=>$phone]);

        $token=$this->getToken(intval($uid));
        return $token;
    }

    /**
     * @param Request $request
     * @return bool|string|\think\Response      返回bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function sendCode(Request $request)
    {
        $code=rand(1000,9999);
        $arr=$request->post();
        $phone=$arr['phone'];
        Cache::store('redis')->set($phone,1234);
        $arr=[
            'msg'=>'发送成功',
            'code'=>200
        ];
        return json_encode($arr);
        $reg="#^1[3456789]\d{9}$#";
        if(!$phone || !preg_match($reg,$phone)){
            return response('Internal Server Error',500);
        }
        Cache::store('redis')->set($phone,$code);
        $host = "https://dxyzm.market.alicloudapi.com";
        $path = "/chuangxin/dxjk";
        $method = "POST";
        $appcode = Env::get('code.appcode', '');
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "content=【创信】你的验证码是：".$code."，3分钟内有效！&mobile=$phone";
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $res=$this->http_curl($method,$url,$headers,$host);
//        var_dump($res);
        return $res;
    }


    /**
     * curl 请求
     * @param string $method    请求方法
     * @param string $url       url地址
     * @param array $headers    header头
     * @param string $host      主机地址
     * @return bool             返回bool
     */
    private function http_curl(string $method,string $url,array $headers,string $host)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_exec($curl);
        $res=curl_getinfo($curl,CURLINFO_HTTP_CODE);
        if($res==200){
            return true;
        }
        return false;
    }

    /**
     * jwt加密
     * @param int $uid
     * @return false|string|\think\Response
     */
    private function getToken(int $uid){
        $key=Env::get('jwt.jwt_key', '');
        if(!$key){
            return response('Internal Server Error',500);
        }
        $token = [
            "iss"=>"",  //签发者 可以为空
            "aud"=>"", //面象的用户，可以为空
            "iat" => time(), //签发时间
            "nbf" => time(), //在什么时候jwt开始生效  （这里表示生成100秒后才生效）
            "exp" => time()+7200, //token 过期时间
            "uid" => $uid //记录的userid的信息，这里是自已添加上去的，如果有其它信息，可以再添加数组的键值对
        ];

        $jwt=JWT::encode($token,$key,'HS256');
        $arr=[
            'msg'=>'ok',
            'token'=>$jwt,
            'uid'=>$uid
        ];
        return json_encode($arr);
    }
}
