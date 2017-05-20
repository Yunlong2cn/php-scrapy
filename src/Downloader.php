<?php
namespace yunlong2cn\ps;


class Downloader
{
    public static function fetch($url, $args = [], $body = true)
    {
        $client = new \GuzzleHttp\Client();
        $method = isset($args['method']) ? strtoupper($args['method']) : 'GET';
        // Log::debug("method = $method");
        $option = [
            'allow_redirects' => [
                'max' => 10
            ]
        ];
        if(isset($args['data'])) $option['form_params'] = $args['data'];

        $option = Helper::merge($option, $args);
        
        try {
            $response = $client->request($method, $url, $option);
            // $response->getParams()->set('redirect.max', 100);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            Log::info('网络请求异常， URL = ' . $url);
            Log::debug($e->getMessage());
            return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            Log::info('GuzzleHttp\Exception\ClientException');
            return false;
        } catch(\GuzzleHttp\Exception\TooManyRedirectsException $e) {
            Log::debug($e->getMessage());
            Log::warn('重定向次数太多');
            return false;
        } catch(\GuzzleHttp\Exception\ConnectException $e) {
            Log::debug($e->getMessage());
            return false;
        } catch(Exception $e) {
            return false;
        }

        
        $httpCode = $response->getStatusCode();
        if($httpCode != 200) {
            Log::info("statusCode = $httpCode");
        }

        // print_r($response->getHeader('content-type'));

        $return = $body ? $response->getBody() : $response;

        // echo($return);

        return $return;
    }

}