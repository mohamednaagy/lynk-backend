<?php

namespace Modules\Otpify\Exceptions;

use Exception;
use Throwable;

class OtpifyVerificationException extends Exception
{
    protected array $data = [];

    public function __construct($message = "", $code = 0, array $data = [], Throwable $previous = null)
    {
        $this->data = $data;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        //
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'data' => $this->data
        ], $this->getCode());
    }
}
