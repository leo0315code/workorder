<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // 短信验证码：demo（本地演示，验证码写日志并返回）| aliyun | tencent
    'sms' => [
        'driver' => env('SMS_DRIVER', 'demo'),
        'allow_demo_code' => env('SMS_ALLOW_DEMO_CODE', true),
        'aliyun' => [
            'access_key_id' => env('ALIYUN_SMS_ACCESS_KEY_ID'),
            'access_key_secret' => env('ALIYUN_SMS_ACCESS_KEY_SECRET'),
            'sign_name' => env('ALIYUN_SMS_SIGN_NAME'),
            'template_code' => env('ALIYUN_SMS_TEMPLATE_CODE'),
        ],
        'tencent' => [
            'secret_id' => env('TENCENT_SMS_SECRET_ID'),
            'secret_key' => env('TENCENT_SMS_SECRET_KEY'),
            'sdk_app_id' => env('TENCENT_SMS_SDK_APP_ID'),
            'sign_name' => env('TENCENT_SMS_SIGN_NAME'),
            'template_id' => env('TENCENT_SMS_TEMPLATE_ID'),
        ],
    ],

    // 微信扫码登录（开放平台网站应用）；留空则走演示模式
    'wechat' => [
        'appid' => env('WECHAT_APPID'),
        'secret' => env('WECHAT_SECRET'),
        // 注意：不要在配置文件中调用 url()（控制台加载时无 request），运行时再解析
        'redirect_url' => env('WECHAT_REDIRECT_URL'),
    ],

];
