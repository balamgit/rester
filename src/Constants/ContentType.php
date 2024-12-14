<?php

namespace Itsmg\Rester\Constants;

enum ContentType: string {
    case JSON = 'json';
    case FORM_PARAMS = 'form_params';
    case MULTIPART = 'multipart';
    case BODY = 'body';
}
