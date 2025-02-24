<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>开奖</title>
</head>
<body>
<div class="lottery-code">
    <div class="tk-lottery-box">
        <ul>
            <li>
                <div class="num class1">50</div>
                <div class="sx">马/土</div>
            </li>
            <li>
                <div class="num class2">50</div>
                <div class="sx">马/土</div>
            </li>
            <li>
                <div class="num class3">50</div>
                <div class="sx">马/土</div>
            </li>
            <li>
                <div class="num class1">50</div>
                <div class="sx">马/土</div>
            </li>
            <li>
                <div class="num class1">50</div>
                <div class="sx">马/土</div>
            </li>
            <li>
                <div class="num class1">50</div>
                <div class="sx">马/土</div>
            </li>
            <li>
                <div class="num class1">50</div>
                <div class="sx">马/土</div>
            </li>
        </ul>
    </div>
</div>
</body>
</html>

<style>
    .lottery-code{
        padding: 15px;
        background: #f1f1f1;
    }
    .tk-lottery-box ul{
        display: block;
        padding: 0;
        margin: 0;
        display: flex;
        align-items: center;
    }
    .tk-lottery-box ul li{
        width: 13.5%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    .tk-lottery-box ul li:nth-child(7) {
        width: 20% !important;
        align-items: flex-end;
        position: relative;
    }
    .tk-lottery-box ul li:nth-child(7):after {
        content: "+";
        position: absolute;
        color: #50637f;
        background-size: 100%;
        text-align: center;
        border-radius: 100%;
        left: 8px;
        top: 5px;
        font-size: 20px;
        font-weight: bold;

    }
    .num{
        width: 35px;
        height: 35px;
        line-height: 35px;
        text-align: center;
        justify-content: center;
        background-size: 100%;
        color: #50637f;
        font-weight: bold;
    }
    .sx{
        margin-top: 4px;
        font-size: 12px;
        color: #50637f;
    }
    .class1{
        background-image: url('/images/red.png');
    }
    .class2{
        background-image: url('/images/blue.png');
    }
    .class3{
        background-image: url('/images/greed.png');
    }
</style>
