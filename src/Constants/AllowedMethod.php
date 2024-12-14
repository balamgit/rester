<?php

namespace Itsmg\Rester\Constants;

enum AllowedMethod: string {
    case POST = 'post';
    case GET = 'get';
    case PUT = 'put';
    case PATCH = 'patch';
    case DELETE = 'delete';
}
