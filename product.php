<?php session_start(); ?>

<?php require 'common.php'; ?>
<?php require 'header.php'; ?>
<?php require 'menu.php'; ?>

<div class="container">
    <!-- ページタイトル -->
     <div class="layout">
        <div class="search-section">
            <form id="filter-form" action="product.php" method="post" class="advanced-search">
                <div class="search-filter">
                    <?php
                    $sql_query = "SELECT MIN(price) AS minprice, MAX(price) AS maxprice, 
                                    MIN(button_count) AS minbutton, MAX(button_count) AS maxbutton, 
                                    MIN(width) AS minwidth, MAX(width) AS maxwidth, 
                                    MIN(depth) AS mindepth, MAX(depth) AS maxdepth, 
                                    MIN(height) AS minheight, MAX(height) AS maxheight,
                                    MIN(weight) AS minweight, MAX(weight) AS maxweight, 
                                    MIN(polling_rate) AS minpoll, MAX(polling_rate) AS maxpoll, 
                                    MIN(battery_life_hours) AS minlife, MAX(battery_life_hours) AS maxlife, 
                                    MIN(dpi_max) AS mindpi, MAX(dpi_max) AS maxdpi 
                                    FROM `product`";
                    $sql = $pdo->prepare($sql_query);
                    $sql->execute();
                    $dbdata = $sql->fetch();
                    $kbndata = [];
                    $count = 0;
                    for ($i = 0; $i <= 25; $i++){
                        if($i != 10 && $i != 11 && $i != 12 && $i != 13 && $i != 14 && $i != 15 && $i != 16 && $i != 20){
                            $kbnid = $i + 1;
                            
                            // スイッチフィルターの場合はスイッチメーカー（kbn=21）のデータを取得
                            $target_kbn = ($kbnid == 8) ? 21 : $kbnid;
                            if (in_array($target_kbn, [1, 2, 3, 4, 6, 7, 19, 23, 25])) {
                                // 直接productテーブルのカラムを参照するフィルター
                                $sql=$pdo->prepare('SELECT m.master_id, k.kbn_id, k.kbn_name AS kbnname, m.name AS name FROM master m 
                                                    INNER JOIN product p ON (
                                                        (m.kbn = 1 AND p.maker_id = m.master_id) OR
                                                        (m.kbn = 2 AND p.color_id = m.master_id) OR
                                                        (m.kbn = 3 AND p.connection_id = m.master_id) OR
                                                        (m.kbn = 4 AND p.sensor_id = m.master_id) OR
                                                        (m.kbn = 6 AND p.interface_id = m.master_id) OR
                                                        (m.kbn = 7 AND p.shape_id = m.master_id) OR
                                                        (m.kbn = 19 AND p.surface_finish_id = m.master_id) OR
                                                        (m.kbn = 23 AND p.charging_port_id = m.master_id) OR
                                                        (m.kbn = 25 AND p.size_category = m.master_id)
                                                    )
                                                    INNER JOIN kubun k ON k.kbn_id = m.kbn 
                                                    WHERE m.kbn = ? 
                                                    GROUP BY m.master_id, m.name 
                                                    ORDER BY m.name');
                            } else {
                                // product_master_relationテーブルを使用するフィルター
                                $sql=$pdo->prepare('SELECT DISTINCT m.master_id, k.kbn_id, k.kbn_name AS kbnname, m.name AS name FROM master m 
                                                    INNER JOIN product_master_relation pmr ON pmr.master_id = m.master_id 
                                                    INNER JOIN product p ON p.id = pmr.product_id 
                                                    INNER JOIN kubun k ON k.kbn_id = m.kbn 
                                                    WHERE m.kbn = ? 
                                                    AND pmr.kbn_id = ?
                                                    GROUP BY m.master_id, m.name 
                                                    ORDER BY m.name');
                            }
                            $sql->bindParam(1,$target_kbn);
                            if (!in_array($target_kbn, [1, 2, 3, 4, 6, 7, 19, 23, 25])) {
                                $sql->bindParam(2,$target_kbn);
                            }
                            $sql->execute();
                            $kbndata[$i] = $sql->fetchAll();
                            $count++;
                        }
                    }
                    ?>
                    <label  class="search-label">検索フィルター</label>
                    <?php
                    if (!isset($_SESSION['filter'])){
                        $_SESSION['filter'] = [];
                    }
                    if (!isset($_SESSION['filter-range'])){
                        $_SESSION['filter-range'] = [];
                    }
                    if (isset($_REQUEST['filter'])){
                        $array = explode('-',$_REQUEST['filter']);
                        $filter_kbn_id = (int)$array[0];
                        $filter_master_id = (int)$array[1];
                        
                        // スイッチメーカーの場合は適切な配列インデックスを取得
                        $array_index = ($filter_kbn_id == 21) ? 7 : $filter_kbn_id - 1;
                        
                        if (isset($kbndata[$array_index])) {
                            $arrays = array_column($kbndata[$array_index],'master_id');
                            $kbnnamerow = array_search($filter_master_id, $arrays);
                            if ($kbnnamerow !== false && !in_array($kbndata[$array_index][$kbnnamerow],$_SESSION['filter'])) {
                                // kbn_idを実際の値に設定
                                $filter_data = $kbndata[$array_index][$kbnnamerow];
                                $filter_data['kbn_id'] = $filter_kbn_id;
                                array_push($_SESSION['filter'], $filter_data);
                            }
                        }
                    }

                    if (isset($_REQUEST['filteritem-del'])) {
                        $targetarray = explode('-',$_REQUEST['filteritem-del']);
                        $targetrow = key(array_filter($_SESSION['filter'], function($v) use ($targetarray) {
    return $v['kbn_id'] === $targetarray[0] && $v['master_id'] === $targetarray[1];
}));
                        unset($_SESSION['filter'][$targetrow]);
                        //Indexを詰める
                        $_SESSION['filter'] = array_values($_SESSION['filter']);
                    }
                    

                    if (isset($_REQUEST['filter-range-del'])) {
                        $targetarrays = array_column($_SESSION['filter-range'][0],'key');
                        $targetrow = array_search( $_REQUEST['filter-range-del'],$targetarrays);
                        unset($_SESSION['filter-range'][$targetrow]);
                        //Indexを詰める
                        $_SESSION['filter-range'] = array_values($_SESSION['filter-range']);
                    }

                    if (isset($_REQUEST['filter-del'])){
                        unset($_SESSION['filter']);
                        unset($_SESSION['filter-range']);
                    }

                    function rangepush($rangearray) {
                        if (isset($_SESSION['filter-range'])) {
                            $rangearrays = array_column($_SESSION['filter-range'],'key');
                            if (in_array($rangearray['key'],$rangearrays)){
                                $targetrange = array_search($rangearray['key'],$rangearrays);
                                unset($_SESSION['filter-range'][$targetrange]);
                                $_SESSION['filter-range'] = array_values($_SESSION['filter-range']);
                            }
                        array_push($_SESSION['filter-range'],$rangearray);
                        }
                    }

                    if (isset($_REQUEST['pricerange'])) {
                        $rangearray = array('key'=>1,'name'=>'price','min'=>$_REQUEST['pricerange']['min'],'max'=>$_REQUEST['pricerange']['max']);
                        rangepush($rangearray);
                    } else if (isset($_REQUEST['weightrange'])) {
                        $rangearray = array('key'=>2,'name'=>'weight','min'=>$_REQUEST['weightrange']['min'],'max'=>$_REQUEST['weightrange']['max']);
                        rangepush($rangearray);
                    } else if (isset($_REQUEST['btnrange'])) {
                        $rangearray = array('key'=>3,'name'=>'btn','min'=>$_REQUEST['btnrange']['min'],'max'=>$_REQUEST['btnrange']['max']);
                        rangepush($rangearray);
                    } else if (isset($_REQUEST['widthrange'])) {
                        $rangearray = array('key'=>4,'name'=>'width','min'=>$_REQUEST['widthrange']['min'],'max'=>$_REQUEST['widthrange']['max']);
                        rangepush($rangearray);
                    } else if (isset($_REQUEST['depthrange'])) {
                        $rangearray = array('key'=>5,'name'=>'depth','min'=>$_REQUEST['depthrange']['min'],'max'=>$_REQUEST['depthrange']['max']);
                        rangepush($rangearray);
                    } else if (isset($_REQUEST['heightrange'])) {
                        $rangearray = array('key'=>6,'name'=>'height','min'=>$_REQUEST['heightrange']['min'],'max'=>$_REQUEST['heightrange']['max']);
                        rangepush($rangearray);
                    } else if (isset($_REQUEST['maxliferange'])) {
                        $rangearray = array('key'=>7,'name'=>'maxlife','min'=>$_REQUEST['maxliferange']['min'],'max'=>$_REQUEST['maxliferange']['max']);
                        rangepush($rangearray);
                    } else if (isset($_REQUEST['pollrange'])) {
                        $rangearray = array('key'=>8,'name'=>'poll','min'=>$_REQUEST['pollrange']['min'],'max'=>$_REQUEST['pollrange']['max']);
                        rangepush($rangearray);
                    } else if (isset($_REQUEST['dpirange'])) {
                        $rangearray = array('key'=>9,'name'=>'dpi','min'=>$_REQUEST['dpirange']['min'],'max'=>$_REQUEST['dpirange']['max']);
                        rangepush($rangearray);
                    }
                    

                    if (isset($_SESSION['filter'])){
                        foreach ($_SESSION['filter'] as $row) {
                            echo '<button name="filteritem-del" class="filteritem-del-btn btn btn-light" value="'.$row['kbn_id'] .'-' . $row['master_id'].'"><span class="dli-close"></span>'.$row['name'] . '</button>';
                        }
                    }

                    if (isset($_SESSION['filter-range'])){
                        foreach ($_SESSION['filter-range'] as $row) {
                            switch ($row['key']) {
                                case 1:
                                    $rangename = "価格: ";
                                    $rangeunit = "円";
                                    break;
                                case 2:
                                    $rangename = "重量: ";
                                    $rangeunit = "g";
                                    break;
                                case 3:
                                    $rangename = "ボタン: ";
                                    $rangeunit = "個";
                                    break;
                                case 4:
                                    $rangename = "横幅: ";
                                    $rangeunit = "mm";
                                    break;
                                case 5:
                                    $rangename = "奥行: ";
                                    $rangeunit = "mm";
                                    break;
                                case 6:
                                    $rangename = "高さ: ";
                                    $rangeunit = "mm";
                                    break;
                                case 7:
                                    $rangename = "駆動時間: ";
                                    $rangeunit = "時間";
                                    break;
                                case 8:
                                    $rangename = "ポーリングレート: ";
                                    $rangeunit = "Hz";
                                    break;
                                case 9:
                                    $rangename = "最大DPI: ";
                                    $rangeunit = "dpi";
                                    break;
                            }
                            echo '<button name="filter-range-del" class="filteritem-del-btn btn btn-light" value="'.$row['key'].'">
                                    <span class="dli-close"></span>'. $rangename . number_format($row['min']) .  $rangeunit .' ~ ' . number_format($row['max']) . $rangeunit . '</button>';
                        }
                    }
                    ?>
                    <button name="filter-del" class="filter-del-button btn btn-outline-secondary">すべて消去</button>
                </div>
                <?php 
                
                if (isset($_SESSION['filter-range'])){
                    foreach($_SESSION['filter-range'] as $row){
                        switch ($row['key']){
                            case 1:
                                $setvalrange1 = '<input type="range" id="example" class="slider-lower" value="' . $row['min'] 
                                            . '" min="0" max="' .$dbdata['maxprice']. '" data-pb-color="var(--bs-secondary-bg)">'
                                            . '<input type="range" class="slider-upper" value="' .$row['max']
                                             . '" min="0" max="' .$dbdata['maxprice']. '" data-pb-color="var(--bs-primary)">';
                                $setvalnum1 = '<input type="number" class="number-box min-box" placeholder="' .number_format($row['min']) . '" value="">'
                                            . '<span> ~ </span>'
                                            . '<input type="number" class="number-box max-box" placeholder="' .number_format($row['max']). '" value="">';
                                break;
                            case 2:
                                $setvalrange2 = '<input type="range" id="example" class="slider-lower" value="' . $row['min'] 
                                            . '" min="0" max="' .$dbdata['maxweight']. '" data-pb-color="var(--bs-secondary-bg)">'
                                            . '<input type="range" class="slider-upper" value="' .$row['max']
                                             . '" min="0" max="' .$dbdata['maxweight']. '" data-pb-color="var(--bs-primary)">';
                                $setvalnum2 = '<input type="number" class="number-box min-box" placeholder="' .number_format($row['min']) . '" value="">'
                                            . '<span> ~ </span>'
                                            . '<input type="number" class="number-box max-box" placeholder="' .number_format($row['max']). '" value="">';
                                break;
                            case 3:
                                $setvalrange3 = '<input type="range" id="example" class="slider-lower" value="' . $row['min'] 
                                            . '" min="0" max="' .$dbdata['maxbutton']. '" data-pb-color="var(--bs-secondary-bg)">'
                                            . '<input type="range" class="slider-upper" value="' .$row['max']
                                             . '" min="0" max="' .$dbdata['maxbutton']. '" data-pb-color="var(--bs-primary)">';
                                $setvalnum3 = '<input type="number" class="number-box min-box" placeholder="' .number_format($row['min']) . '" value="">'
                                            . '<span> ~ </span>'
                                            . '<input type="number" class="number-box max-box" placeholder="' .number_format($row['max']). '" value="">';
                                break;
                            case 4:
                                $setvalrange4 = '<input type="range" id="example" class="slider-lower" value="' . $row['min'] 
                                            . '" min="0" max="' .$dbdata['maxwidth']. '" data-pb-color="var(--bs-secondary-bg)">'
                                            . '<input type="range" class="slider-upper" value="' .$row['max']
                                             . '" min="0" max="' .$dbdata['maxwidth']. '" data-pb-color="var(--bs-primary)">';
                                $setvalnum4 = '<input type="number" class="number-box min-box" placeholder="' .number_format($row['min']) . '" value="">'
                                            . '<span> ~ </span>'
                                            . '<input type="number" class="number-box max-box" placeholder="' .number_format($row['max']). '" value="">';
                                break;
                            case 5:
                                $setvalrange5 = '<input type="range" id="example" class="slider-lower" value="' . $row['min'] 
                                            . '" min="0" max="' .$dbdata['maxdepth']. '" data-pb-color="var(--bs-secondary-bg)">'
                                            . '<input type="range" class="slider-upper" value="' .$row['max']
                                             . '" min="0" max="' .$dbdata['maxdepth']. '" data-pb-color="var(--bs-primary)">';
                                $setvalnum5 = '<input type="number" class="number-box min-box" placeholder="' .number_format($row['min']) . '" value="">'
                                            . '<span> ~ </span>'
                                            . '<input type="number" class="number-box max-box" placeholder="' .number_format($row['max']). '" value="">';
                                break;
                            case 6:
                                $setvalrange6 = '<input type="range" id="example" class="slider-lower" value="' . $row['min'] 
                                            . '" min="0" max="' .$dbdata['maxheight']. '" data-pb-color="var(--bs-secondary-bg)">'
                                            . '<input type="range" class="slider-upper" value="' .$row['max']
                                             . '" min="0" max="' .$dbdata['maxheight']. '" data-pb-color="var(--bs-primary)">';
                                $setvalnum6 = '<input type="number" class="number-box min-box" placeholder="' .number_format($row['min']) . '" value="">'
                                            . '<span> ~ </span>'
                                            . '<input type="number" class="number-box max-box" placeholder="' .number_format($row['max']). '" value="">';
                                break;
                            case 7:
                                $setvalrange7 = '<input type="range" id="example" class="slider-lower" value="' . $row['min'] 
                                            . '" min="0" max="' .$dbdata['maxlife']. '" data-pb-color="var(--bs-secondary-bg)">'
                                            . '<input type="range" class="slider-upper" value="' .$row['max']
                                             . '" min="0" max="' .$dbdata['maxlife']. '" data-pb-color="var(--bs-primary)">';
                                $setvalnum7 = '<input type="number" class="number-box min-box" placeholder="' .number_format($row['min']) . '" value="">'
                                            . '<span> ~ </span>'
                                            . '<input type="number" class="number-box max-box" placeholder="' .number_format($row['max']). '" value="">';
                                break;
                            case 8:
                                $setvalrange8 = '<input type="range" id="example" class="slider-lower" value="' . $row['min'] 
                                            . '" min="0" max="' .$dbdata['maxpoll']. '" data-pb-color="var(--bs-secondary-bg)">'
                                            . '<input type="range" class="slider-upper" value="' .$row['max']
                                             . '" min="0" max="' .$dbdata['maxpoll']. '" data-pb-color="var(--bs-primary)">';
                                $setvalnum8 = '<input type="number" class="number-box min-box" placeholder="' .number_format($row['min']) . '" value="">'
                                            . '<span> ~ </span>'
                                            . '<input type="number" class="number-box max-box" placeholder="' .number_format($row['max']). '" value="">';
                                break;
                            case 9:
                                $setvalrange9 = '<input type="range" id="example" class="slider-lower" value="' . $row['min'] 
                                            . '" min="0" max="' .$dbdata['maxdpi']. '" data-pb-color="var(--bs-secondary-bg)">'
                                            . '<input type="range" class="slider-upper" value="' .$row['max']
                                             . '" min="0" max="' .$dbdata['maxdpi']. '" data-pb-color="var(--bs-primary)">';
                                $setvalnum9 = '<input type="number" class="number-box min-box" placeholder="' .number_format($row['min']) . '" value="">'
                                            . '<span> ~ </span>'
                                            . '<input type="number" class="number-box max-box" placeholder="' .number_format($row['max']). '" value="">';
                                break;
                        }
                    }
                }
                ?>
                <div class="range-group" data-name="pricerange">
                    <dl class="acdn">
                        <dt>
                            <label for="price_range" class="search-label">
                            価格帯
                            </label>
                        </dt>
                        <dd>
                            <div class="multi-slider">
                                <?php
                                if (isset($setvalrange1)){
                                    echo $setvalrange1;
                                } else {
                                    echo '<input type="range" id="example" class="slider-lower" value="' .$dbdata['minprice']. '" min="0" max="' .$dbdata['maxprice']. '" data-pb-color="var(--bs-secondary-bg)">';
                                    echo '<input type="range" class="slider-upper" value="' .$dbdata['maxprice']. '" min="0" max="' .$dbdata['maxprice']. '" data-pb-color="var(--bs-primary)">';
                                }
                                ?>
                            </div>
                            <div class="number-container">
                                <?php
                                if (isset($setvalnum1)){
                                    echo $setvalnum1;
                                } else {
                                    echo '<input type="number" class="number-box min-box" placeholder="' .number_format($dbdata['minprice']). '" value="">';
                                    echo '<span> ~ </span>';
                                    echo '<input type="number" class="number-box max-box" placeholder="' .number_format($dbdata['maxprice']). '" value="">';
                                }
                                ?>
                            </div>
                        </dd>
                    </dl>
                </div>
                <div class="range-group" data-name="weightrange">
                    <dl class="acdn">
                        <dt>
                            <label class="search-label">
                                重量
                            </label>
                        </dt>
                        <dd>
                            <div class="multi-slider">
                                <?php
                                if (isset($setvalrange2)){
                                    echo $setvalrange2;
                                } else {
                                    echo '<input type="range" class="slider-lower" value="' .number_format($dbdata['minweight']). '" min="0" max="' .$dbdata['maxweight']. '" data-pb-color="var(--bs-secondary-bg)">';
                                    echo '<input type="range" class="slider-upper" value="' .number_format($dbdata['maxweight']). '" min="0" max="' .$dbdata['maxweight']. '" data-pb-color="var(--bs-primary)">';
                                }
                                ?>
                            </div>
                            <div class="number-container">
                                <?php
                                if (isset($setvalnum2)){
                                    echo $setvalnum2;
                                } else {
                                    echo '<input type="number" class="number-box min-box" placeholder="' .number_format($dbdata['minweight']). '" value="">';
                                    echo '<span> ~</span>';
                                    echo '<input type="number" class="number-box max-box" placeholder="' .number_format($dbdata['maxweight']). '" value="">';
                                }
                                ?>
                            </div>
                        </dd>
                    </dl>
                </div>
                <div class="range-group" data-name="btnrange">
                    <dl class="acdn">
                        <dt>
                            <label class="search-label">
                                ボタン数
                            </label>
                        </dt>
                        <dd>
                            <div class="multi-slider">
                                <?php
                                if (isset($setvalrange3)){
                                    echo $setvalrange3;
                                } else {
                                    echo '<input type="range" class="slider-lower" value="' .number_format($dbdata['minbutton']). '" min="0" max="' .$dbdata['maxbutton']. '" data-pb-color="var(--bs-secondary-bg)">';
                                    echo '<input type="range" class="slider-upper" value="' .number_format($dbdata['maxbutton']). '" min="0" max="' .$dbdata['maxbutton']. '" data-pb-color="var(--bs-primary)">';
                                }
                                ?>
                            </div>
                            <div class="number-container">
                                <?php
                                if (isset($setvalnum3)){
                                    echo $setvalnum3;
                                } else {
                                    echo '<input type="number" class="number-box min-box" placeholder="' .number_format($dbdata['minbutton']). '" value="">';
                                    echo '<span> ~</span>';
                                    echo '<input type="number" class="number-box max-box" placeholder="' .number_format($dbdata['maxbutton']). '" value="">';
                                }
                                ?>
                            </div>
                        </dd>
                    </dl>
                </div>
                <div class="range-group" data-name="widthrange">
                    <dl class="acdn">
                        <dt>
                            <label class="search-label">
                                横幅
                            </label>
                        </dt>
                        <dd>
                            <div class="multi-slider">
                                <?php
                                if (isset($setvalrange4)){
                                    echo $setvalrange4;
                                } else {
                                    echo '<input type="range" class="slider-lower" value="' .number_format($dbdata['minwidth']). '" min="0" max="' .$dbdata['maxwidth']. '" data-pb-color="var(--bs-secondary-bg)">';
                                    echo '<input type="range" class="slider-upper" value="' .number_format($dbdata['maxwidth']). '" min="0" max="' .$dbdata['maxwidth']. '" data-pb-color="var(--bs-primary)">';
                                }
                                ?>
                            </div>
                            <div class="number-container">
                                <?php
                                if (isset($setvalnum4)){
                                    echo $setvalnum4;
                                } else {
                                    echo '<input type="number" class="number-box min-box" placeholder="' .number_format($dbdata['minwidth']). '" value="">';
                                    echo '<span> ~</span>';
                                    echo '<input type="number" class="number-box max-box" placeholder="' .number_format($dbdata['maxwidth']). '" value="">';
                                }
                                ?>
                            </div>
                        </dd>
                    </dl>
                </div>
                <div class="range-group" data-name="depthrange">
                    <dl class="acdn">
                        <dt>
                            <label class="search-label">
                                奥行
                            </label>
                        </dt>
                        <dd>
                            <div class="multi-slider">
                                <?php
                                if (isset($setvalrange5)){
                                    echo $setvalrange5;
                                } else {
                                    echo '<input type="range" class="slider-lower" value="' .number_format($dbdata['mindepth']). '" min="0" max="' .$dbdata['maxdepth']. '" data-pb-color="var(--bs-secondary-bg)">';
                                    echo '<input type="range" class="slider-upper" value="' .number_format($dbdata['maxdepth']). '" min="0" max="' .$dbdata['maxdepth']. '" data-pb-color="var(--bs-primary)">';
                                }
                                ?>
                            </div>
                            <div class="number-container">
                                <?php
                                if (isset($setvalnum5)){
                                    echo $setvalnum5;
                                } else {
                                    echo '<input type="number" class="number-box min-box" placeholder="' .number_format($dbdata['mindepth']). '" value="">';
                                    echo '<span> ~</span>';
                                    echo '<input type="number" class="number-box max-box" placeholder="' .number_format($dbdata['maxdepth']). '" value="">';
                                }
                                ?>
                            </div>
                        </dd>
                    </dl>
                </div>
                <div class="range-group" data-name="heightrange">
                    <dl class="acdn">
                        <dt>
                            <label class="search-label">
                                高さ
                            </label>
                        </dt>
                        <dd>
                            <div class="multi-slider">
                                <?php
                                if (isset($setvalrange6)){
                                    echo $setvalrange6;
                                } else {
                                    echo '<input type="range" class="slider-lower" value="' .$dbdata['minheight']. '" min="0" max="' .$dbdata['maxheight']. '" data-pb-color="var(--bs-secondary-bg)">';
                                    echo '<input type="range" class="slider-upper" value="' .$dbdata['maxheight']. '" min="0" max="' .$dbdata['maxheight']. '" data-pb-color="var(--bs-primary)">';
                                }
                                ?>
                            </div>
                            <div class="number-container">
                                <?php
                                if (isset($setvalnum6)){
                                    echo $setvalnum6;
                                } else {
                                    echo '<input type="number" class="number-box min-box" placeholder="' .$dbdata['minheight']. '" value="">';
                                    echo '<span> ~ </span>';
                                    echo '<input type="number" class="number-box max-box" placeholder="' .$dbdata['maxheight']. '" value="">';
                                }
                                ?>
                            </div>
                        </dd>
                    </dl>
                </div>
                <div class="range-group" data-name="maxliferange">
                    <dl class="acdn">
                        <dt>
                            <label class="search-label">
                                駆動時間
                            </label>
                        </dt>
                        <dd>
                            <div class="multi-slider">
                                <?php
                                if (isset($setvalrange7)){
                                    echo $setvalrange7;
                                } else {
                                    echo '<input type="range" class="slider-lower" value="' .$dbdata['minlife']. '" min="0" max="' .$dbdata['maxlife']. '" data-pb-color="var(--bs-secondary-bg)">';
                                    echo '<input type="range" class="slider-upper" value="' .$dbdata['maxlife']. '" min="0" max="' .$dbdata['maxlife']. '" data-pb-color="var(--bs-primary)">';
                                }
                                ?>
                            </div>
                            <div class="number-container">
                                <?php
                                if (isset($setvalnum7)){
                                    echo $setvalnum7;
                                } else {
                                    echo '<input type="number" class="number-box min-box" placeholder="' .$dbdata['minlife']. '" value="">';
                                    echo '<span> ~</span>';
                                    echo '<input type="number" class="number-box max-box" placeholder="' .$dbdata['maxlife']. '" value="">';
                                }
                                ?>
                            </div>
                        </dd>
                    </dl>
                </div>
                <div class="range-group" data-name="pollrange">
                    <dl class="acdn">
                        <dt>
                            <label class="search-label">
                                ポーリングレート
                            </label>
                        </dt>
                        <dd>
                            <div class="multi-slider">
                                <?php
                                if (isset($setvalrange8)){
                                    echo $setvalrange8;
                                } else {
                                    echo '<input type="range" class="slider-lower" value="' .$dbdata['minpoll']. '" min="0" max="' .$dbdata['maxpoll']. '" data-pb-color="var(--bs-secondary-bg)">';
                                    echo '<input type="range" class="slider-upper" value="' .$dbdata['maxpoll']. '" min="0" max="' .$dbdata['maxpoll']. '" data-pb-color="var(--bs-primary)">';
                                }
                                ?>
                            </div>
                            <div class="number-container">
                                <?php
                                if (isset($setvalnum8)){
                                    echo $setvalnum8;
                                } else {
                                    echo '<input type="number" class="number-box min-box" placeholder="' .$dbdata['minpoll']. '" value="">';
                                    echo '<span> ~</span>';
                                    echo '<input type="number" class="number-box max-box" placeholder="' .$dbdata['maxpoll']. '" value="">';
                                }
                                ?>
                            </div>
                        </dd>
                    </dl>
                </div>
                <div class="range-group" data-name="dpirange">
                    <dl class="acdn">
                        <dt>
                            <label class="search-label">
                                最大DPI
                            </label>
                        </dt>
                        <dd>
                            <div class="multi-slider">
                                <?php
                                if (isset($setvalrange9)){
                                    echo $setvalrange9;
                                } else {
                                    echo '<input type="range" class="slider-lower" value="' .$dbdata['mindpi']. '" min="0" max="' .$dbdata['maxdpi']. '" data-pb-color="var(--bs-secondary-bg)">';
                                    echo '<input type="range" class="slider-upper" value="' .$dbdata['maxdpi']. '" min="0" max="' .$dbdata['maxdpi']. '" data-pb-color="var(--bs-primary)">';
                                }
                                ?>
                            </div>
                            <div class="number-container">
                                <?php
                                if (isset($setvalnum9)){
                                    echo $setvalnum9;
                                } else {
                                    echo '<input type="number" class="number-box min-box" placeholder="' .$dbdata['mindpi']. '" value="">';
                                    echo '<span> ~</span>';
                                    echo '<input type="number" class="number-box max-box" placeholder="' .$dbdata['maxdpi']. '" value="">';
                                }
                                ?>
                            </div>
                        </dd>
                    </dl>
                </div>
                <?php 
                for ($i = 0; $i <= $count; $i++) {
                    if (isset($kbndata[$i][0]['kbnname'])){ 
                        if (!($i === 10 || $i === 11 || $i === 13 || $i === 14 || $i === 16)){
                            echo '<dl class="acdn">';
                            echo '<dt>';
                            echo '<label class="search-label">';
                            // スイッチフィルターの場合は「スイッチメーカー」と表示
                            $display_name = ($i == 7) ? 'スイッチメーカー' : $kbndata[$i][0]['kbnname'];
                            echo $display_name;
                            echo '</label>';
                            echo '</dt>';

                            echo '<dd>';
                                                        foreach ($kbndata[$i] as $row){
                                // スイッチフィルターの場合はkbn_id=21（スイッチメーカー）として扱う
                                $filter_kbn_id = ($i == 7) ? 21 : $row['kbn_id'];
                                $filter_value = $filter_kbn_id . '-' . $row['master_id'];
                                
                                if (isset($_SESSION['filter']) && array_filter($_SESSION['filter'], function($v) use ($filter_kbn_id, $row) {
                                    return $v['kbn_id'] == $filter_kbn_id && $v['master_id'] == $row['master_id'];
                                })) {
                                    echo '<button name="filter" value="'.$filter_value.'" class="filter-btn btn btn-outline-secondary"><input type="checkbox" class="form-check-input" name="'.$row['kbnname'].'" value="'.$row['name'].'" checked disabled>' .$row['name'].'</button>';
                                } else {
                                    echo '<button name="filter" value="'.$filter_value.'" class="filter-btn btn btn-outline-secondary"><input type="checkbox" class="form-check-input" name="'.$row['kbnname'].'" value="'.$row['name'].'" disabled>' .$row['name'].'</button>';
                                }
                                
                            }
                            echo '</dd>';
                            echo '</dl>';
                        }
                    }
                }
                ?>
            </form>
        </div>
        <div class="output-form">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-mouse"></i>
                    マウス
                </h1>
            </div>

            <!-- 商品一覧 -->
            <div class="products-section">
                <?php
                // 検索条件の構築
                $where_conditions = [];
                $params = [];

                // キーワード検索
                if (isset($_REQUEST['keyword']) && !empty($_REQUEST['keyword'])) {
                    $where_conditions[] = "name LIKE ?";
                    $params[] = '%' . $_REQUEST['keyword'] . '%';
                }

                // 価格帯フィルター
                if (isset($_REQUEST['price_range']) && !empty($_REQUEST['price_range'])) {
                    $price_range = $_REQUEST['price_range'];
                    if ($price_range === '0-5000') {
                        $where_conditions[] = "price <= 5000";
                    } elseif ($price_range === '5000-10000') {
                        $where_conditions[] = "price > 5000 AND price <= 10000";
                    } elseif ($price_range === '10000-20000') {
                        $where_conditions[] = "price > 10000 AND price <= 20000";
                    } elseif ($price_range === '20000-') {
                        $where_conditions[] = "price > 20000";
                    }
                }

                //条件フィルター 
                
                if (isset($_SESSION['filter-range']) && !empty($_SESSION['filter-range'])) {
                    foreach ($_SESSION['filter-range'] as $row) {
                        switch ($row['key']) {
                            case 1 : //価格
                                $where_conditions[] = 'price BETWEEN ' . (int)$row['min'] . ' AND '. (int)$row['max'];
                                break;
                            case 2 : //重量
                                $where_conditions[] = '(weight IS NULL OR weight BETWEEN ' . (int)$row['min'] . ' AND '. (int)$row['max'] . ')';
                                break;
                            case 3 : //ボタン数
                                $where_conditions[] = '(button_count IS NULL OR button_count BETWEEN ' . (int)$row['min'] . ' AND '. (int)$row['max'] . ')';
                                break;
                            case 4 : //横幅
                                $where_conditions[] = '(width IS NULL OR width BETWEEN ' . (int)$row['min'] . ' AND '. (int)$row['max'] . ')';
                                break;
                            case 5 : //奥行
                                $where_conditions[] = '(depth IS NULL OR depth BETWEEN ' . (int)$row['min'] . ' AND '. (int)$row['max'] . ')';
                                break;
                            case 6 : //高さ
                                $where_conditions[] = '(height IS NULL OR height BETWEEN ' . (int)$row['min'] . ' AND '. (int)$row['max'] . ')';
                                break;
                            case 7 : //駆動時間
                                $where_conditions[] = '(battery_life_hours IS NULL OR battery_life_hours BETWEEN ' . (int)$row['min'] . ' AND '. (int)$row['max'] . ')';
                                break;
                            case 8 : //ポーリングレート
                                $where_conditions[] = '(polling_rate IS NULL OR polling_rate BETWEEN ' . (int)$row['min'] . ' AND '. (int)$row['max'] . ')';
                                break;
                            case 9 : //最大DPI
                                $where_conditions[] = '(dpi_max IS NULL OR dpi_max BETWEEN ' . (int)$row['min'] . ' AND '. (int)$row['max'] . ')';
                                break;
                        }

                    }
                }
                if (isset($_SESSION['filter']) && !empty($_SESSION['filter'])) {
                    foreach ($_SESSION['filter'] as $row) {
                        switch ($row['kbn_id']) {
                            case 1 : //メーカー
                                $where_conditions[] = "maker_id =" . $row['master_id'];
                                break;
                            case 2 : //色
                                $where_conditions[] = "color_id =" . $row['master_id'];
                                break;
                            case 3 : //接続方式
                                $where_conditions[] = "connection_id =" . $row['master_id'];
                                break;
                            case 4 : //センサータイプ
                                $where_conditions[] = "sensor_id =" . $row['master_id'];
                                break;
                            case 5 : //センサー名
                                $where_conditions[] = "id IN (
                                    SELECT pmr.product_id 
                                    FROM product_master_relation pmr 
                                    WHERE pmr.kbn_id = 5 AND pmr.master_id = " . (int)$row['master_id'] . "
                                )";
                                break;
                            case 6 : //インターフェイス
                                $where_conditions[] = "interface_id =" . $row['master_id'];
                                break;
                            case 7 : //シェイプ
                                $where_conditions[] = "shape_id =" . $row['master_id'];
                                break;
                            case 8 : //スイッチメーカー
                                $where_conditions[] = "id IN (
                                    SELECT pmr.product_id 
                                    FROM product_master_relation pmr 
                                    WHERE pmr.kbn_id = 21 AND pmr.master_id = " . (int)$row['master_id'] . "
                                )";
                                break;
                            case 9 : //スイッチ製造メーカー
                                //$where_conditions[] = " =" . $row['master_id'];
                                break;
                            case 10 : //国
                                //$where_conditions[] = " =" . $row['master_id'];
                                break;
                            case 11 : //地域
                                //$where_conditions[] = " =" . $row['master_id'];
                                break;
                            case 12 : //都道府県警
                                //$where_conditions[] = " =" . $row['master_id'];
                                break;
                            case 13 : //公式サイトURL
                                //$where_conditions[] = " =" . $row['master_id'];
                                break;
                            case 14 : //DPI
                                //$where_conditions[] = "maker_id =" . $row['master_id'];
                                break;
                            case 15 : //ポーリングレート
                                //$where_conditions[] = " =" . $row['master_id'];
                                break;
                            case 16 : //バッテリー容量
                                $where_conditions[] = "battery_capacity_mah =" . $row['master_id'];
                                break;
                            case 17 : //バッテリー持続時間
                                //$where_conditions[] = "maker_id =" . $row['master_id'];
                                break;
                            case 18 : //材質
                                $where_conditions[] = "id IN (
                                    SELECT pmr.product_id 
                                    FROM product_master_relation pmr 
                                    WHERE pmr.kbn_id = 18 AND pmr.master_id = " . (int)$row['master_id'] . "
                                )";
                                break;
                            case 19 : //表面士上げ
                                $where_conditions[] = "surface_finish_id =" . $row['master_id'];
                                break;
                            case 20 : //LOD設定
                                $lod_value = ($row['master_id'] == 1) ? 1 : 0;
                                $where_conditions[] = "lod_adjustable = " . $lod_value;
                                break;
                            case 8 : //スイッチメーカー（表示上はkbn_id=8だが実際は21のデータ）
                            case 21 : //スイッチメーカー
                                $where_conditions[] = "id IN (
                                    SELECT pmr.product_id 
                                    FROM product_master_relation pmr 
                                    WHERE pmr.kbn_id = 21 AND pmr.master_id = " . (int)$row['master_id'] . "
                                )";
                                break;
                            case 22 : //MCUチップ
                                $where_conditions[] = "id IN (
                                    SELECT pmr.product_id 
                                    FROM product_master_relation pmr 
                                    WHERE pmr.kbn_id = 22 AND pmr.master_id = " . (int)$row['master_id'] . "
                                )";
                                break;
                            case 23 : //充電端子
                                $where_conditions[] = "charging_port_id =" . $row['master_id'];
                                break;
                            case 24 : //ソフトウェア
                                $where_conditions[] = "id IN (
                                    SELECT pmr.product_id 
                                    FROM product_master_relation pmr 
                                    WHERE pmr.kbn_id = 24 AND pmr.master_id = " . (int)$row['master_id'] . "
                                )";
                                break;
                            case 25 : //サイズカテゴリー
                                $where_conditions[] = "size_category =" . $row['master_id'];
                                break;
                        }
                    }
                }

                // 基本の絞り込み条件（すべての商品を表示）

                // WHERE句の構築
                $where_clause = '';
                if (!empty($where_conditions)) {
                    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
                }

                // ORDER BY句の構築
                $order_clause = 'ORDER BY recommend DESC, id ASC'; // デフォルト
                if (isset($_REQUEST['sort'])) {
                    switch ($_REQUEST['sort']) {
                        case 'price_low':
                            $order_clause = 'ORDER BY price ASC';
                            break;
                        case 'price_high':
                            $order_clause = 'ORDER BY price DESC';
                            break;
                        case 'newest':
                            $order_clause = 'ORDER BY created_day DESC, id DESC';
                            break;
                        default:
                            $order_clause = 'ORDER BY recommend DESC, sales_quantity DESC, id ASC';
                    }
                }

                // クエリ実行
                $sql_query = "SELECT * FROM product $where_clause $order_clause";
                $sql = $pdo->prepare($sql_query);
                $sql->execute($params);

                $products = $sql->fetchAll();
                $product_count = count($products);
                ?>
                <div class="products-header">
                    <div class="result-count">
                        <i class="fas fa-list"></i>
                        <span><?= $product_count ?>件の商品が見つかりました</span>
                    </div>
                    
                    <!-- 並び替え -->
                    <div class="sort-section">
                        <div class="sort-options">
                            <span class="sort-label">並び替え：</span>
                            <a href="product.php?<?= http_build_query(array_merge($_GET, $_POST, ['sort' => 'default'])) ?>"
                                class="sort-link <?= (!isset($_REQUEST['sort']) || $_REQUEST['sort'] === 'default') ? 'active' : '' ?>">
                                おすすめ順
                            </a>
                            <a href="product.php?<?= http_build_query(array_merge($_GET, $_POST, ['sort' => 'price_low'])) ?>"
                                class="sort-link <?= (isset($_REQUEST['sort']) && $_REQUEST['sort'] === 'price_low') ? 'active' : '' ?>">
                                価格が安い順
                            </a>
                            <a href="product.php?<?= http_build_query(array_merge($_GET, $_POST, ['sort' => 'price_high'])) ?>"
                                class="sort-link <?= (isset($_REQUEST['sort']) && $_REQUEST['sort'] === 'price_high') ? 'active' : '' ?>">
                                価格が高い順
                            </a>
                            <a href="product.php?<?= http_build_query(array_merge($_GET, $_POST, ['sort' => 'newest'])) ?>"
                                class="sort-link <?= (isset($_REQUEST['sort']) && $_REQUEST['sort'] === 'newest') ? 'active' : '' ?>">
                                新着順
                            </a>
                        </div>
                    </div>
                </div>

                <div class="product-grid">
                    <?php
                    
                    //現在の基本ポイント付与率取得
                    $id = 1;
                    $sql = $pdo->prepare('SELECT campaign_point_rate FROM point_campaign 
                                        WHERE point_campaign_id = ?');
                    $sql->bindParam(1, $id);
                    $sql->execute();
                    $data = $sql->fetch();
                    
                    if ($product_count > 0) {
                        foreach ($products as $row) {
                            $images = getImage($row['id'], $pdo);
                            $id = $row['id'];

                            // 画像パスの設定（image_name1をメインに修正）
                            $image_path = "images/{$images[0]}.jpg";  // image_name1を使用
                            if (!file_exists($image_path)) {
                                $image_path = "images/no-image.jpg";
                            }

                            // 接続方式を取得
                            $conn_sql = $pdo->prepare('SELECT m.name FROM product_master_relation pmr 
                                JOIN master m ON pmr.master_id = m.master_id 
                                WHERE pmr.product_id = ? AND pmr.kbn_id = 3');
                            $conn_sql->bindParam(1, $id);
                            $conn_sql->execute();
                            $connections = $conn_sql->fetchAll(PDO::FETCH_COLUMN);
                            $is_wireless = false;
                            foreach ($connections as $conn) {
                                if (strpos($conn, 'ワイヤレス') !== false || strpos($conn, '無線') !== false || strpos($conn, 'Bluetooth') !== false) {
                                    $is_wireless = true;
                                    break;
                                }
                            }

                            // センサー情報を取得
                            $sensor_sql = $pdo->prepare('SELECT m.name FROM product_master_relation pmr 
                                JOIN master m ON pmr.master_id = m.master_id 
                                WHERE pmr.product_id = ? AND pmr.kbn_id = 5 LIMIT 1');
                            $sensor_sql->bindParam(1, $id);
                            $sensor_sql->execute();
                            $sensor = $sensor_sql->fetchColumn();

                            echo '<div class="product-card">';
                            echo '<div class="product-image">';

                            // バッジ表示（複数バッジ対応）
                            if ($row['recommend']) {
                                echo '<div class="product-badge recommend">おすすめ</div>';
                            } elseif ($row['on_sale']) {
                                echo '<div class="product-badge sale">SALE</div>';
                            } elseif ((time() - strtotime($row['created_day'])) < 30 * 24 * 60 * 60) {
                                echo '<div class="product-badge new">NEW</div>';
                            } elseif ($row['stock_quantity'] <= 5 && $row['stock_quantity'] > 0) {
                                echo '<div class="product-badge limited">残少</div>';
                            }

                            // ワイヤレスインジケーター
                            if ($is_wireless) {
                                echo '<div class="wireless-indicator" title="ワイヤレス対応">';
                                echo '<i class="fas fa-wifi"></i>';
                                echo '</div>';
                            }

                            // お気に入りボタン
                            echo '<button class="favorite-btn" data-product-id="', $id, '" aria-label="お気に入りに追加">';
                            echo '<i class="far fa-heart"></i>';
                            echo '</button>';

                            echo '<img src="', $image_path, '" alt="', h($row['name']), '" loading="lazy">';
                            echo '</div>';

                            echo '<div class="product-info">';
                            echo '<h3 class="product-title">';
                            echo '<a href="detail.php?id=', $id, '">', h($row['name']), '</a>';
                            echo '</h3>';

                            // 価格表示（セール時は元価格も表示）
                            echo '<div class="price-wrapper">';
                            echo '<div class="product-price">¥', number_format($row['price']), '</div>';
                            if ($row['on_sale']) {
                                $original_price = $row['price'] * 1.2; // 仮の元価格
                                echo '<div class="price-compare">¥', number_format($original_price), '</div>';
                            }
                            echo '</div>';

                            //ポイント表示
                            $pointsum = (int)($row['price'] * $data['campaign_point_rate']);
                            $percentage = $data['campaign_point_rate'];
                            $sql = $pdo->prepare('SELECT pc.campaign_point_rate FROM point_campaign pc
                                                INNER JOIN campaign_target ct ON ct.point_campaign_id = pc.point_campaign_id 
                                                WHERE pc.point_campaign_id != ? 
                                                AND pc.del_kbn = 0 
                                                AND pc.start_date <= NOW()
                                                AND pc.end_date > NOW()
                                                AND ct.target_id = ?');
                            $sql->bindParam(1, $id);
                            $sql->bindParam(2, $row['id']);
                            $sql->execute();
                            $campaignrate = $sql->fetch();
                            if (($campaignrate)){
                                $pointsum += (int)($row['price'] * $campaignrate['campaign_point_rate']);
                                $percentage += $campaignrate['campaign_point_rate'];
                            }
                        
                            echo '<p>'. number_format($pointsum,0) .' point (' .($percentage * 100). '%)</p>';
                        

                            // 特徴的なハイライト表示
                            echo '<div class="feature-highlights">';

                            // 超軽量マウスの強調
                            if ($row['weight'] < 50) {
                                echo '<span class="feature-tag highlight">';
                                echo '<i class="fas fa-feather-alt"></i>';
                                echo '超軽量 ', $row['weight'], 'g';
                                echo '</span>';
                            }

                            // 高性能センサー
                            if ($sensor && (strpos($sensor, '3950') !== false || strpos($sensor, '3395') !== false || strpos($sensor, 'Focus Pro') !== false)) {
                                echo '<span class="feature-tag highlight">';
                                echo '<i class="fas fa-microchip"></i>';
                                echo 'フラグシップセンサー';
                                echo '</span>';
                            }

                            // 8KHz対応
                            if ($row['polling_rate'] >= 8000) {
                                echo '<span class="feature-tag highlight">';
                                echo '<i class="fas fa-bolt"></i>';
                                echo '8KHz対応';
                                echo '</span>';
                            }

                            // Motion Sync
                            if ($row['motion_sync_support']) {
                                echo '<span class="feature-tag">';
                                echo '<i class="fas fa-sync"></i>';
                                echo 'Motion Sync';
                                echo '</span>';
                            }

                            // バッテリー持続時間（長時間の場合）
                            if ($row['battery_life_hours'] >= 70) {
                                echo '<span class="feature-tag">';
                                echo '<i class="fas fa-battery-full"></i>';
                                echo $row['battery_life_hours'], '時間駆動';
                                echo '</span>';
                            }

                            echo '</div>';

                            // パフォーマンスメーター（視覚的な性能表示）
                            echo '<div class="performance-meter">';

                            // スピード（DPIベース）
                            $speed_percent = min(100, ($row['dpi_max'] / 36000) * 100);
                            echo '<div class="meter-item">';
                            echo '<div class="meter-label">Speed</div>';
                            echo '<div class="meter-bar">';
                            echo '<div class="meter-fill speed" style="width: ', $speed_percent, '%"></div>';
                            echo '</div>';
                            echo '</div>';

                            // 精度（ポーリングレートベース）
                            $precision_percent = min(100, ($row['polling_rate'] / 8000) * 100);
                            echo '<div class="meter-item">';
                            echo '<div class="meter-label">Precision</div>';
                            echo '<div class="meter-bar">';
                            echo '<div class="meter-fill precision" style="width: ', $precision_percent, '%"></div>';
                            echo '</div>';
                            echo '</div>';

                            // 軽量性（重量ベース - 逆比例）
                            $lightweight_percent = max(0, min(100, ((150 - $row['weight']) / 150) * 100));
                            echo '<div class="meter-item">';
                            echo '<div class="meter-label">Agility</div>';
                            echo '<div class="meter-bar">';
                            echo '<div class="meter-fill lightweight" style="width: ', $lightweight_percent, '%"></div>';
                            echo '</div>';
                            echo '</div>';

                            echo '</div>';

                            // レビュー評価（仮のデータ）
                            $rating = rand(35, 50) / 10; // 3.5〜5.0の仮評価
                            $review_count = rand(10, 200);
                            echo '<div class="product-rating">';
                            echo '<div class="stars">';
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= floor($rating)) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif ($i - 0.5 <= $rating) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            echo '</div>';
                            echo '<span class="rating-count">(', $review_count, ')</span>';
                            echo '</div>';

                            // 在庫状況
                            echo '<div class="stock-status">';
                            if ($row['stock_quantity'] > 10) {
                                echo '<span class="stock-available"><i class="fas fa-check-circle"></i>在庫あり</span>';
                            } elseif ($row['stock_quantity'] > 0) {
                                echo '<span class="stock-limited"><i class="fas fa-exclamation-triangle"></i>残り', $row['stock_quantity'], '個</span>';
                            } else {
                                echo '<span class="stock-out"><i class="fas fa-times-circle"></i>在庫切れ</span>';
                            }
                            echo '</div>';

                            echo '<div class="product-actions">';
                            echo '<a href="detail.php?id=', $id, '" class="btn btn-outline">';
                            echo '<i class="fas fa-info-circle"></i>詳細';
                            echo '</a>';

                            if ($row['stock_quantity'] > 0) {
                                echo '<button class="btn btn-primary add-to-cart" data-product-id="', $id, '">';
                                echo '<i class="fas fa-cart-plus"></i>カートに追加';
                                echo '</button>';
                            } else {
                                echo '<button class="btn btn-disabled" disabled>';
                                echo '<i class="fas fa-ban"></i>在庫切れ';
                                echo '</button>';
                            }
                            echo '</div>';

                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="no-products">';
                        echo '<div class="no-products-icon"><i class="fas fa-search"></i></div>';
                        echo '<h3>商品が見つかりませんでした</h3>';
                        echo '<p>検索条件を変更して再度お試しください。</p>';
                        echo '<a href="product.php" class="btn btn-primary">すべての商品を見る</a>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* お気に入りバッジアニメーション */
.favorite-bounce {
    animation: favoriteBounce 0.6s ease-in-out;
}

@keyframes favoriteBounce {
    0%, 20%, 60%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    80% { transform: translateY(-5px); }
}

@keyframes favoriteCountPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); background: #f06292; }
    100% { transform: scale(1); }
}

/* お気に入り通知のスタイル */
.favorite-notification .notification-icon.favorite-icon {
    background: #e91e63;
}

/* 通知システムのスタイル（product.php用） */
.cart-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    border: 1px solid #e5e7eb;
    min-width: 320px;
    z-index: 10000;
    transform: translateX(400px);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.cart-notification.show {
    transform: translateX(0);
    opacity: 1;
}

.cart-notification.hide {
    transform: translateX(400px);
    opacity: 0;
}

.notification-content {
    display: flex;
    align-items: center;
    padding: 15px;
    gap: 12px;
}

.notification-icon {
    background: #10b981;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-text {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.notification-product {
    font-size: 0.9em;
    color: #6b7280;
    margin-bottom: 2px;
}

.notification-price {
    font-weight: 600;
    color: #2563eb;
    font-size: 0.95em;
}

.notification-actions {
    flex-shrink: 0;
}

.notification-close {
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: all 0.3s;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-close:hover {
    background: #f9fafb;
    color: #1f2937;
}
</style>

<script>

// 商品データをJavaScriptに埋め込み
const productData = {
    <?php
    // 現在のページの全商品データをJavaScriptに渡す
    if (isset($products) && !empty($products)) {
        foreach ($products as $product) {
            echo $product['id'] . ': {';
            echo 'id: ' . $product['id'] . ',';
            echo 'name: ' . json_encode($product['name']) . ',';
            echo 'price: ' . $product['price'] . ',';
            echo 'tax_id: ' . $product['tax_id'] . ',';
            echo 'stock_quantity: ' . $product['stock_quantity'];
            echo '},';
        }
    }
    ?>};

// 初期カート数を設定
let cartItemCount = <?= isset($_SESSION['product']) ? count($_SESSION['product']) : 0 ?>;

// お気に入りボタンの処理
document.querySelectorAll('.favorite-btn').forEach(btn => {
    const productId = btn.dataset.productId;
    const isLoggedIn = <?= isset($_SESSION['customer']) ? 'true' : 'false' ?>;

    // ログイン時は初期状態をチェック
    if (isLoggedIn) {
        fetch('favorite-insert.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `id=${productId}&action=check`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.is_favorite) {
                    btn.classList.add('active');
                    btn.innerHTML = '<i class="fas fa-heart"></i>';
                }
            })
            .catch(error => console.error('Error:', error));
    }

    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        if (!isLoggedIn) {
            if (confirm('お気に入りに追加するにはログインが必要です。\nログインページに移動しますか？')) {
                window.location.href = 'login-input.php';
            }
            return;
        }

        this.disabled = true;

        fetch('favorite-insert.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `id=${productId}&action=toggle`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.is_favorite) {
                        // お気に入りに追加された場合
                        favoriteItemCount++;
                        this.classList.add('active');
                        this.innerHTML = '<i class="fas fa-heart"></i>';
                        
                        // お気に入り数を更新してアニメーション
                        updateFavoriteBadge(favoriteItemCount);
                        animateFavoriteBadge();
                        
                        // 商品名を取得して通知表示
                        const productCard = this.closest('.product-card');
                        const productName = productCard.querySelector('.product-title').textContent;
                        showAddToFavoriteNotification(productName);
                    } else {
                        // お気に入りから削除された場合
                        favoriteItemCount--;
                        this.classList.remove('active');
                        this.innerHTML = '<i class="far fa-heart"></i>';
                        
                        // お気に入り数を更新
                        updateFavoriteBadge(favoriteItemCount);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('エラーが発生しました');
            })
            .finally(() => {
                this.disabled = false;
            });
    });
});



// 現在のお気に入り数を取得
let favoriteItemCount = <?php 
    if (isset($_SESSION['customer'])) {
        $fav_count_sql = $pdo->prepare('SELECT COUNT(*) as count FROM favorite WHERE customer_id = ?');
        $fav_count_sql->bindParam(1, $_SESSION['customer']['id']);
        $fav_count_sql->execute();
        echo $fav_count_sql->fetch()['count'];
    } else {
        echo '0';
    }
?>;

// ページ読み込み時にカート数を表示
document.addEventListener('DOMContentLoaded', function() {
    updateCartBadge(cartItemCount);
});

// カート数バッジの更新
function updateCartBadge(count) {
    const cartLink = document.querySelector('.cart-link');
    let cartBadge = cartLink.querySelector('.cart-count');

    if (!cartBadge) {
        cartBadge = document.createElement('span');
        cartBadge.className = 'cart-count';
        cartLink.appendChild(cartBadge);
    }

    if (count > 0) {
        cartBadge.textContent = count > 99 ? '99+' : count;
        cartBadge.classList.add('show');
        console.log('Cart badge updated:', count);
    } else {
        cartBadge.classList.remove('show');
    }
}

// カート数更新アニメーション
function animateCartBadge() {
    const cartBadge = document.querySelector('.cart-count');
    if (cartBadge) {
        cartBadge.classList.add('pulse');
        setTimeout(() => {
            cartBadge.classList.remove('pulse');
        }, 600);
    }
}

// カートに追加ボタンの処理
document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const productId = parseInt(this.dataset.productId);
        const productCard = this.closest('.product-card');
        const productName = productCard.querySelector('.product-title a').textContent;
        const productPrice = productCard.querySelector('.product-price').textContent;

        // 商品データを取得
        const product = productData[productId];
        if (!product) {
            alert('商品情報が見つかりません');
            return;
        }

        if (product.stock_quantity <= 0) {
            alert('申し訳ございません。この商品は在庫切れです。');
            return;
        }

        // ボタンを無効化してローディング状態に
        this.disabled = true;
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 追加中...';
        this.classList.add('loading');

        // カートに追加（FormDataを使用）
        const formData = new FormData();
        formData.append('id', product.id);
        formData.append('name', product.name);
        formData.append('price', product.price);
        formData.append('count', 1);
        formData.append('tax', product.tax_id);

        fetch('cart-insert.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // レスポンスが成功かチェック
                if (response.ok || response.redirected) {
                    // カート数を即座に更新
                    cartItemCount++;
                    updateCartBadge(cartItemCount);
                    animateCartBadge();

                    console.log('Cart updated, new count:', cartItemCount);

                    // 成功時のアニメーション
                    this.classList.add('success');
                    this.innerHTML = '<i class="fas fa-check"></i> 追加完了!';

                    // カートアニメーション
                    createCartAnimation(productCard, this);

                    // 通知表示
                    showAddToCartNotification(productName, productPrice);

                    setTimeout(() => {
                        this.classList.remove('success', 'loading');
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 2000);
                } else {
                    throw new Error('カートへの追加に失敗しました');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.classList.add('error');
                this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> エラー';

                setTimeout(() => {
                    this.classList.remove('error', 'loading');
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 2000);

                alert('カートへの追加に失敗しました: ' + error.message);
            });
    });
});

// カートアニメーション
function createCartAnimation(productCard, button) {
    const productImg = productCard.querySelector('.product-image img');
    const cartIcon = document.querySelector('.cart-link');

    if (!productImg || !cartIcon) return;

    // 商品画像をクローン
    const flyingImg = productImg.cloneNode(true);
    flyingImg.style.position = 'fixed';
    flyingImg.style.width = '60px';
    flyingImg.style.height = '60px';
    flyingImg.style.zIndex = '9999';
    flyingImg.style.pointerEvents = 'none';
    flyingImg.style.borderRadius = '8px';
    flyingImg.style.transition = 'all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
    flyingImg.style.boxShadow = '0 4px 20px rgba(0,0,0,0.3)';

    // 開始位置を設定
    const imgRect = productImg.getBoundingClientRect();
    flyingImg.style.left = imgRect.left + 'px';
    flyingImg.style.top = imgRect.top + 'px';

    document.body.appendChild(flyingImg);

    // カートアイコンの位置を取得
    const cartRect = cartIcon.getBoundingClientRect();

    // アニメーション開始
    setTimeout(() => {
        flyingImg.style.left = (cartRect.left + cartRect.width / 2 - 30) + 'px';
        flyingImg.style.top = (cartRect.top + cartRect.height / 2 - 30) + 'px';
        flyingImg.style.transform = 'scale(0.3) rotate(360deg)';
        flyingImg.style.opacity = '0.8';
    }, 50);

    // カートアイコンを揺らす
    cartIcon.classList.add('cart-bounce');
    setTimeout(() => {
        cartIcon.classList.remove('cart-bounce');
    }, 1000);

    // アニメーション終了後にクリーンアップ
    setTimeout(() => {
        if (flyingImg.parentNode) {
            flyingImg.remove();
        }
    }, 850);
}

// 追加完了通知
function showAddToCartNotification(productName, productPrice) {
    const notification = document.createElement('div');
    notification.className = 'cart-notification';
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="notification-text">
                <div class="notification-title">カートに追加しました</div>
                <div class="notification-product">${productName}</div>
                <div class="notification-price">${productPrice}</div>
            </div>
            <div class="notification-actions">
                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="notification-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(notification);

    // アニメーションで表示
    setTimeout(() => notification.classList.add('show'), 10);

    // 3秒後に自動消去
    setTimeout(() => {
        notification.classList.add('hide');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 3000);
}

// お気に入りバッジ更新関数
function updateFavoriteBadge(count) {
    const favoriteBadge = document.querySelector('.favorite-count');
    if (favoriteBadge) {
        favoriteBadge.textContent = count > 99 ? '99+' : count;
        if (count > 0) {
            favoriteBadge.classList.add('show');
            favoriteBadge.style.display = 'inline-block';
        } else {
            favoriteBadge.classList.remove('show');
            favoriteBadge.style.display = 'none';
        }
    } else if (count > 0) {
        // バッジが存在しない場合は新規作成
        const favoriteLink = document.querySelector('.favorite-link');
        if (favoriteLink) {
            // まず親要素にrelativeを設定
            favoriteLink.style.position = 'relative';
            
            const newBadge = document.createElement('span');
            newBadge.textContent = count > 99 ? '99+' : count;
            
            // 全てのスタイルを明示的に設定（CSSに依存しない）
            Object.assign(newBadge.style, {
                position: 'absolute',
                top: '-10px',
                right: '-15px',
                background: '#e91e63',
                color: 'white',
                borderRadius: '50%',
                width: '20px',
                height: '20px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: '0.7rem',
                fontWeight: '600',
                border: '2px solid white',
                boxShadow: '0 2px 4px rgba(233, 30, 99, 0.4)',
                transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
                opacity: '0',
                transform: 'scale(0)',
                zIndex: '10'
            });
            
            favoriteLink.appendChild(newBadge);
            
            // DOM追加後、アニメーション開始
            requestAnimationFrame(() => {
                newBadge.style.opacity = '1';
                newBadge.style.transform = 'scale(1)';
            });
        }
    }
}

// お気に入りバッジアニメーション
function animateFavoriteBadge() {
    const favoriteIcon = document.querySelector('.favorite-link');
    const favoriteBadge = document.querySelector('.favorite-count');
    
    if (favoriteIcon) {
        favoriteIcon.classList.add('favorite-bounce');
        setTimeout(() => {
            favoriteIcon.classList.remove('favorite-bounce');
        }, 600);
    }
    
    // バッジ自体にもパルスアニメーション
    if (favoriteBadge) {
        favoriteBadge.style.animation = 'favoriteCountPulse 0.6s ease-in-out';
        setTimeout(() => {
            favoriteBadge.style.animation = '';
        }, 600);
    }
}

// お気に入り追加通知
function showAddToFavoriteNotification(productName) {
    const notification = document.createElement('div');
    notification.className = 'cart-notification favorite-notification';
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon favorite-icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="notification-text">
                <div class="notification-title">お気に入りに追加しました</div>
                <div class="notification-product">${productName}</div>
            </div>
            <div class="notification-actions">
                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="notification-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(notification);

    // アニメーションで表示
    setTimeout(() => notification.classList.add('show'), 10);

    // 3秒後に自動消去
    setTimeout(() => {
        notification.classList.add('hide');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 3000);
}
</script>
<?php require 'footer.php'; ?>  