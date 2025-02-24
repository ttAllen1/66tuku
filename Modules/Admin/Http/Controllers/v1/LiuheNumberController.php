<?php
/**
 * 六合号码管理控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Http\Requests\LiuheNumberRequest;
use Modules\Admin\Services\liuhe\NumberService;
use Modules\Common\Exceptions\ApiException;

class LiuheNumberController extends BaseApiController
{

    public function a()
    {
        // Define the pool of numbers to choose from
        $pool = range(1, 20);

        // Generate five sets of random winning numbers
        $winningNumbers = array();
        for ($i = 0; $i < 5; $i++) {
            shuffle($pool);
            $winningNumbers[] = array_slice($pool, 0, 7);
        }

        // Initialize the counts to 0
        $counts = array_fill_keys($pool, 0);


        // Count the occurrences of each number in each set of winning numbers
        foreach ($winningNumbers as $winningNumber) {
            foreach ($winningNumber as $number) {
                $counts[$number]++;
            }
        }
        dd($winningNumbers,$counts, $pool);

        // Calculate the misses for each number
        $misses = array();
        foreach ($pool as $number) {
            $misses[$number] = count($winningNumbers) - $counts[$number];
        }

        print_r($winningNumbers);

        // Print the misses for each number
        foreach ($misses as $number => $miss) {
            echo "Number $number has a miss of $miss\n";
        }

    }

    public function b()
    {


        // 定义数字范围和每组开奖号码的数量
        $numbers = range(1, 10);
        $num_per_draw = 7;

        // 定义开奖号码数组
        $draws = array(
            array(1, 3, 5, 7, 9, 10, 2),
            array(2, 4, 6, 8, 10, 9, 7),
            array(1, 5, 7, 9, 2, 4, 6),
            array(3, 6, 9, 1, 4, 7, 10),
            array(4, 7, 10, 3, 6, 2, 5)
        );

//        Number 1 has a count of 1
//        Number 2 has a count of 0
//        Number 3 has a count of 0
//        Number 4 has a count of 0
//        Number 5 has a count of 0
//        Number 6 has a count of 0
//        Number 7 has a count of 0
//        Number 8 has a count of 3
//        Number 9 has a count of 1
//        Number 10 has a count of 0

        // 定义数组来保存每个数字的遗漏次数
        $counts = array_fill_keys($numbers, 0);

        // 计算每个数字的遗漏次数
        foreach ($numbers as $number) {
            foreach ($draws as $draw) {
                if (in_array($number, $draw)) {
                    $counts[$number] = 0;
                } else {
                    $counts[$number]++;
                }
            }
        }
        print_r($numbers);
        // 输出每个数字的遗漏次数
        foreach ($counts as $number => $count) {
            echo "Number $number has a count of $count\n";
        }

    }

    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new NumberService())->index($request->all());
    }

    public function update(LiuheNumberRequest $request)
    {
        $request->validate();

        return (new NumberService())->update($request->input('id', 0), $request->all());
    }

    /**
     * 生成新的一年的数据
     * @param LiuheNumberRequest $request
     * @return JsonResponse
     * @throws ApiException
     */
    public function create(LiuheNumberRequest $request): JsonResponse
    {
        $request->validate('create');

        return (new NumberService())->create($request->all());
    }
}
