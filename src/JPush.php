<?php

namespace YanlongMa\Push;

/**
 * JPush
 *
 * @author Yanlong Ma
 */
class JPush
{

    private $appKey;

    private $masterSecret;

    private $pushApi = 'https://api.jpush.cn/v3/push';

    // 针对 iOS 平台, true 表示推送生产环境，false 表示要推送开发环境 如果不指定则为推送生产环境
    private $apnsProduction;

    /**
     * JPush constructor.
     *
     * @param $appKey
     * @param $masterSecret
     * @param bool $apnsProduction
     */
    public function __construct($appKey, $masterSecret, $apnsProduction = false)
    {
        $this->appKey = $appKey;
        $this->masterSecret = $masterSecret;
        $this->apnsProduction = $apnsProduction;
    }

    /**
     * 获取 Authorization 认证需要的头信息
     *
     * @return array
     */
    private function getHeader()
    {
        return [
            'Content-Type: application/json',
            'Authorization:Basic ' . base64_encode("{$this->appKey}:{$this->masterSecret}"),
        ];
    }

    /**
     * Push API
     *
     * @param string $alert 弹框信息
     * @param array $extras 自定义字段
     * @param array $platform 推送平台
     *          默认推所有，根据自己需要选择平台 ["android", "ios", "winphone"]
     * @param array $audience 推送目标
     *          默认推所有，支持别名、标签、注册ID、分群、广播等
     *          alias 推送最多支持1000个,外部注意处理
     *          [
     *             'alias' => ['13888888888', '13866666666'],
     *          ]
     * @return bool
     */
    public function push($alert = 'JPush', array $extras = [], array $platform = [], array $audience = [])
    {
        $platform = count($platform) == 0 ? 'all' : $platform;
        $audience = count($audience) == 0 ? 'all' : $audience;

        $postData = [
            // 推送到所有平台为"all" { "platform" : "all" }
            // 指定特定推送平台 { "platform" : ["android", "ios"] }  目前支持 "android", "ios", "winphone"
            'platform' => $platform,
            // 如果要发广播（全部设备）则直接填写"all"  { "audience" : "all" }
            // 支持别名、标签、注册ID、分群、广播等
            'audience' => $audience,
            'notification' => [
                'ios' => [
                    'alert' => $alert,
                    'sound' => 'default',
                    'badge' => '+1',
                    'extras' => $extras
                ],
                'android' => [
                    'alert' => $alert,
                    'title' => '',
                    'builder_id' => 1,
                    'extras' => $extras
                ]
            ],
            'options' => [
                'apns_production' => $this->apnsProduction === true ? true : false,
            ]
        ];
        // echo json_encode($postData); exit;

        $options = [
            CURLOPT_HTTPHEADER => $this->getHeader()
        ];

        $client = new Client();
        $response = $client->request('POST', $this->pushApi, json_encode($postData), $options);

        if ($response->getStatusCode() != 200) {
            return false;
        }

        // 失败 {"error": {"message": "Authen failed", "code": 1004}}
        // 成功 {sendno: "0",msg_id: "9007201335639395"}
        echo $response->getBody();
        $arr = json_decode($response->getBody(), true);
        if (!array_key_exists('msg_id', $arr) || !array_key_exists('sendno', $arr) || $arr['sendno'] != 0) {
            return false;
        }

        return true;
    }

}