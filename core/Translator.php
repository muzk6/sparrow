<?php


namespace Core;


class Translator
{
    /**
     * @var array 语言文件集合
     */
    protected $lang = [];

    /**
     * 转换成当前语言的文本
     * @param int $code
     * @param array $params
     * @return string
     */
    public function trans(int $code, array $params = [])
    {
        $lang = &$this->lang[APP_LANG];
        if (!isset($lang)) {
            $lang = include(sprintf('%s/%s.php', PATH_LANG, APP_LANG));
        }

        $text = $default = '?';
        if (isset($lang[$code])) {
            $text = $lang[$code];
        } else { // 不存在就取默认语言的文本
            $langConf = config('app.lang');
            if ($langConf != APP_LANG) {
                $lang = &$this->lang[$langConf];
                if (!isset($lang)) {
                    $lang = include(sprintf('%s/%s.php', PATH_LANG, $langConf));
                }
                $text = $lang[$code] ?? $default;
            }
        }

        if ($text != $default && $params) {
            foreach ($params as $k => $v) {
                $text = str_replace("{{$k}}", $v, $text);
            }
        }

        return $text;
    }

}
