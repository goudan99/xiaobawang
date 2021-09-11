<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Constant\Code;
use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Http\Response;
use Exception;
use Throwable;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use App\Repositories\Mobile;
use App\Repositories\Auth;
use App\Http\Requests\PasswordRequest;

class AuthController extends AccessTokenController
{
	
    public function logout(ServerRequestInterface $req)
    { 
        return [
          'code' => Code::SUCCESS,
          'msg' => "success",
          'data' => [],
          'timestamp' => time()
        ];
	}
    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(ServerRequestInterface $req)
    { 
		$param = (array) $req->getParsedBody();	
		$param['grant_type']="password";
		$param['client_id']=config("shop")["auth"]["client"];
		$param['client_secret']=config("shop")["auth"]["secret"];
		$request=$req->withParsedBody($param);
		$requestParameters1 = (array) $request->getParsedBody();

        return $this->withErrorHandling(function () use ($request) {
            return $this->convertResponse(
                $this->server->respondToAccessTokenRequest($request, new Psr7Response)
            );
        });
    }	
	
    /**
     * Convert a PSR7 response to a Illuminate Response.
     *
     * @param \Psr\Http\Message\ResponseInterface $psrResponse
     * @return \Illuminate\Http\Response
     */
    public function convertResponse($psrResponse)
    {
		$data['code']=Code::SUCCESS;
		$data['data']=json_decode($psrResponse->getBody());;
		$data['error']="";
		$data['msg']="success";
		$data['timestamp']=time();
        return new Response(
            json_encode($data),
            $psrResponse->getStatusCode(),
            $psrResponse->getHeaders()
        );
    }
    protected function withErrorHandling($callback)
    {
        try {
            return $callback();
        } catch (OAuthServerException $e) {
			$data['code']=Code::VALIDATE;
			$data['data']=[];
			$data['error']="帐号密或密码错误";
			$data['msg']="帐号密或密码错误";
			$data['timestamp']=time();
			return $data;
        } catch (Exception $e) {
            $this->exceptionHandler()->report($e);

            return new Response($this->configuration()->get('app.debug') ? $e->getMessage() : 'Error.', 500);
        } catch (Throwable $e) {
            $this->exceptionHandler()->report(new FatalThrowableError($e));

            return new Response($this->configuration()->get('app.debug') ? $e->getMessage() : 'Error.', 500);
        }
    }
	
	
    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function code(Request $request)
    { 

		$mobile=new Mobile();
		
		$type=$request->get("type");

		$phone=$request->get("phone");
		
		$code= $mobile->code($phone, '', $type);
		
		$request->session()->put('mobile_code_'.$phone.'_'.$type, $code);
		
        return [
          'code' => Code::SUCCESS,
          'msg' => "验证码发送成功，请注意查收".$code,
          'data' => [],
          'timestamp' => time()
        ];
    }
	
    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function password(PasswordRequest $request)
    { 
		$data =$request->all();
		
		$auth=new Auth();
		
		if($data["code"]!=$request->session()->get('mobile_code_'.$data["phone"].'_'.Mobile::FIND)){
          return [
            'code' =>  Code::VALIDATE,
            'msg' => "验证码不正确",
            'data' => [],
            'timestamp' => time()
          ];
		}
		
		$auth->change($data);
		
		$request->session()->put('mobile_code_'.$data["phone"].'_'.Mobile::FIND,'');//修改完以后清掉这个session值
		
        return [
          'code' => Code::SUCCESS,
          'msg' => "修改成功",
          'data' => [],
          'timestamp' => time()
        ];
    }
}
