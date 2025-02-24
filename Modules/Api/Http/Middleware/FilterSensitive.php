<?php
/**
 * @Name  过滤关键词
 * @Description
 */

namespace Modules\Api\Http\Middleware;

use Closure;
use Modules\Api\Models\Sensitive;
use Modules\Common\Exceptions\CustomException;

class FilterSensitive
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws CustomException
     */
    public function handle($request, Closure $next)
    {
        $keyword = Sensitive::query()->where('status', 1)->pluck('keyword');
        $message = $request->input('message', null);
        $title = $request->input('title', null);
        $content = $request->input('content', null);
        $res = true;
        if ($message) {
            $res = $this->check($message, $keyword);
        }
        if ($title) {
            $res = $this->check($title, $keyword);
        }
        if ($content) {
            $res = $this->check($content, $keyword);
        }
        if ($res) {
            return $next($request);
        }
        throw new CustomException(['message'=>'提交的内容包含敏感词']);
    }

    private function check($message, $keywords)
    {
        foreach ($keywords as $keyword) {
            if (!is_array($message)) {
                if (stripos($message, $keyword) !== false) {
                    return false;
                }
            } else {
                foreach($message as $item) {
                    if (stripos($item, $keyword) !== false) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
