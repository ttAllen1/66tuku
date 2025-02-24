<?php
/**
 * @Name   全局异常处理
 * @Description
 */

namespace Modules\Common\Exceptions;
use BadMethodCallException;
use Error;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use ParseError;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    private $status = 0;
    private $message = '';
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        $this->reportable(function (QueryException $e) {
            Log::channel('_db')->info($e->getMessage());
        })->stop();
        $this->reportable(function (\Exception $e) {
//            Log::info($e->getMessage());
        })->stop();
    }

    /**
     * @Name 定义异常状态
     * @Description
     * @param $e
     */
    private function setErrorException($e)
    {
        if($e instanceof BadMethodCallException){
            $this->status = StatusData::BAD_METHOD_CALL_EXCEPTION;
            $this->message = MessageData::Error;
        }else if($e instanceof Error){
            $this->status = StatusData::Error;
            $this->message = MessageData::Error;
        }else if($e instanceof ParseError){
            $this->status = StatusData::PARES_ERROR;
            $this->message = MessageData::PARES_ERROR;
        }else if($e instanceof \ReflectionException){
            $this->status = StatusData::REFLECTION_EXCEPTION;
            $this->message = MessageData::REFLECTION_EXCEPTION;
        }else if($e instanceof \ErrorException){
            $this->status = StatusData::ERROR_EXCEPTION;
            $this->message = MessageData::ERROR_EXCEPTION;
        }else if($e instanceof \InvalidArgumentException){
            $this->status = StatusData::INVALID_ARGUMENT_EXCEPTION;
            $this->message = MessageData::INVALID_ARGUMENT_EXCEPTION;
        }else if($e instanceof ModelNotFoundException){
            $this->status = StatusData::MODEL_NOT_FOUND_EXCEPTION;
            $this->message = MessageData::MODEL_NOT_FOUND_EXCEPTION;
        }else if($e instanceof FileNotFoundException){
            $this->status = StatusData::FILE_NOT_FOUND_EXCEPTION;
            $this->message = MessageData::FILE_NOT_FOUND_EXCEPTION;
        }else if($e instanceof QueryException){
            $this->status = StatusData::QUERY_EXCEPTION;
            $this->message = MessageData::QUERY_EXCEPTION;
        } else if ($e instanceof TooManyRequestsHttpException) {
            $this->status = StatusData::BAD_REQUEST;
            $this->message = MessageData::TOO_MANY_REQUESTS;
        } else if($e instanceof \RuntimeException){
            $this->status = StatusData::RUNTIME_EXCEPTION;
            $this->message = MessageData::RUNTIME_EXCEPTION;
        }
    }
    public function render($request, Throwable $e)
    {
        if($request->is("api/*")){
            if ($e instanceof ApiException) {
                $result = [
                    "status" => $e->getCode(),
                    "message" => $e->getMessage(),
                ];
                return response()->json($result,CodeData::INTERNAL_SERVER_ERROR);
            } else if ($e instanceof CustomException) {
                $result = [
                    "status" => $e->getCode(),
                    "message" => $e->getMessage(),
                ];
//                dd($e, $result);
                return response()->json($result,CodeData::OK);
            }  else if ($e instanceof AuthorizationException) {
                $result = [
                    "status" => $e->getCode(),
                    "message" => $e->getMessage(),
                ];
                return response()->json($result,CodeData::UNAUTHORIZED);
            } else if($e instanceof ValidationException){
                $result = [
                    "status"=>StatusData::BAD_REQUEST,
                    "message"=>array_values($e->errors())[0][0]
                ];
                return response()->json($result,CodeData::BAD_REQUEST);
            } else if($e instanceof TooManyRequestsHttpException){
                $result = [
                    "status"=>StatusData::TOO_MANY_ATTEMPT,
                    "message"=> MessageData::TOO_MANY_REQUESTS
                ];
                return response()->json($result,CodeData::TOO_MANY_REQUESTS);
            }
			if(true){   // !env("APP_DEBUG")
				$this->setErrorException($e);
				if($this->status){
                    if (env("APP_DEBUG")) {
                        $data = [
                            "file"=>$e->getFile(),
                            "line"=>$e->getLine(),
                            "trace"=>$e->getTrace()
                        ];
                    } else {
                        $data = [];
                    }

					if($this->status == StatusData::MODEL_NOT_FOUND_EXCEPTION){
						$data['message'] = $e->getModel();
					}else{
						$data['message'] = $e->getMessage() ? $e->getMessage() : $this->message;
					}

					return response()->json([
						"status" => $this->status,
						"message" => $data['message'] ?? MessageData::COMMON_EXCEPTION,
						"data"=>$data,
					],CodeData::INTERNAL_SERVER_ERROR);
				}
			}
        }

         return parent::render($request, $e); // TODO: Change the autogenerated stub
    }
}
