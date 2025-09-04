$(function() {
    //レンジスライダー

    //https://qiita.com/paprinco/items/eb23030ee149bb89a5af
    $('.slider-lower, .slider-upper').on('load input', function(e) {
        if (Number($('.slider-lower').val()) >= Number($('.slider-upper').val())) {
            if ($(this).hasClass('slider-lower')) {
                $(this).val(Number($('.slider-upper').val()) - 1);
            } else {
                $(this).val(Number($('.slider-lower').val()) + 1);
            }
        }
        ratio = ($(this).val() / $(this).prop("max")) * 100;
        left = $(this).data('pb-color');
        if ($(this).hasClass('slider-lower')) {
            right = 'rgba(255,255,255,0.0)';
        } else {
            right = 'var(--bs-secondary-bg)';
        }
        $(this).css('background', 'linear-gradient(90deg, ' + left + ' ' + ratio + '%, ' + right + ' ' + ratio + '%)');
    });
    $('.slider-lower, .slider-upper').trigger('input');

    // 変更があった range-group だけ送信
    $('.range-group').each(function() {
        const $group = $(this);

        // スライダー変更時
        $group.find('input[type="range"]').on('change', function() {
            postRangeGroup($group);
        });

        // number入力確定時
        $group.find('input[type="number"]').on('blur', function() {
            if ($(this).val() !== "") {
                postRangeGroup($group);
            }
        });
        $group.find('input[type="number"]').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if ($(this).val() !== "") {
                    postRangeGroup($group);
                }
            }
        });
    });

    // range-group 内の min/max をまとめて POST
    function postRangeGroup($group) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'product.php';

        // 各グループに応じて name を動的に設定できる
        const lower = $group.find('.slider-lower').val();
        const upper = $group.find('.slider-upper').val();
        const namePrefix = $group.data('name'); // デフォルト pricerange

        const inputMin = document.createElement('input');
        inputMin.type = 'hidden';
        inputMin.name = namePrefix + '[min]';
        inputMin.value = lower;
        form.appendChild(inputMin);

        const inputMax = document.createElement('input');
        inputMax.type = 'hidden';
        inputMax.name = namePrefix + '[max]';
        inputMax.value = upper;
        form.appendChild(inputMax);

        document.body.appendChild(form);
        form.submit();
        form.remove();
    }

    // ボタンクリック時のイベントリスナーを設定
    //アコーディオン
    //https://qiita.com/7note/items/254d46c6dfbd5f5bfc1c
    $(".acdn dt").on("click", function() {
        $(this).next("dd").slideToggle();
        $(this).toggleClass("close");
    });


    $('.range-group').each(function() {
        const $group = $(this);
        const $lower = $group.find('.slider-lower');
        const $upper = $group.find('.slider-upper');
        const $minBox = $group.find('.min-box');
        const $maxBox = $group.find('.max-box');

        // スライダー → number 反映
        $lower.on('input', function() {
            $minBox.val($(this).val());
        });
        $upper.on('input', function() {
            $maxBox.val($(this).val());
        });

        // number → スライダー 反映（必要なら）
        $minBox.on('input', function() {
            $lower.val($(this).val()).trigger('input');
        });
        $maxBox.on('input', function() {
            $upper.val($(this).val()).trigger('input');
        });

        // 初期値セット
        $minBox.val($lower.val());
        $maxBox.val($upper.val());
    });
});