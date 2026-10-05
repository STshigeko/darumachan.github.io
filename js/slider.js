// トップページの写真スライド
//  ・自動では切り替わらない。見に来た人が操作したときだけ切り替わる
//    （指ですべらせたときは、前の写真が消えてから次の写真がふわっと出てくる。動きは style.css）
//  ・写真の下に「‹　1 / 2　›」を表示する。矢印を押すと、すぐに前後の写真に切り替わり、
//    数字は「今何枚目 / 全部で何枚」を表す
//  ・スマホでは、写真を指で左右にすべらせても切り替わる
//
// スライドを増やすときは、index.html の .hero__slides の中に
// class="hero__slide" の要素（img など）を足すだけでよい。枚数の表示は自動で増える。
(function () {
  var SWIPE_DISTANCE = 40; // これ以上横に動かしたら「すべらせた」と判定（px）

  var container = document.querySelector('.hero__slides');
  if (!container) return;
  var slides = container.querySelectorAll('.hero__slide');
  if (slides.length < 2) return;

  var current = 0;

  // 「‹　1 / 2　›」を、写真の下に作る
  function makeArrow(label, mark, step) {
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'hero__arrow';
    button.setAttribute('aria-label', label);
    button.textContent = mark;
    button.addEventListener('click', function () {
      showInstantly(current + step);
    });
    return button;
  }

  var pager = document.createElement('div');
  pager.className = 'hero__pager';
  var count = document.createElement('span');
  count.className = 'hero__count';
  count.setAttribute('aria-live', 'polite');
  pager.appendChild(makeArrow('前の写真', '‹', -1));
  pager.appendChild(count);
  pager.appendChild(makeArrow('次の写真', '›', 1));
  container.insertAdjacentElement('afterend', pager);

  function show(index) {
    current = (index + slides.length) % slides.length; // 最後の次は1枚目、1枚目の前は最後
    slides.forEach(function (slide, i) {
      var active = i === current;
      slide.classList.toggle('is-active', active);
      slide.setAttribute('aria-hidden', active ? 'false' : 'true');
    });
    count.textContent = (current + 1) + ' / ' + slides.length;
  }

  // 矢印を押したときは、消える・出てくるの動きをつけず、すぐに切り替える
  function showInstantly(index) {
    container.classList.add('is-instant');
    show(index);
    void container.offsetWidth; // 動きなしの状態を、ここで確定させる
    container.classList.remove('is-instant');
  }

  // 指で左右にすべらせて切り替える（マウスは対象外）
  var startX = 0;
  var startY = 0;
  var tracking = false;

  container.addEventListener('pointerdown', function (e) {
    if (e.pointerType === 'mouse') return;
    tracking = true;
    startX = e.clientX;
    startY = e.clientY;
  });

  container.addEventListener('pointerup', function (e) {
    if (!tracking) return;
    tracking = false;
    var dx = e.clientX - startX;
    var dy = e.clientY - startY;
    if (Math.abs(dx) >= SWIPE_DISTANCE && Math.abs(dx) > Math.abs(dy)) {
      show(dx < 0 ? current + 1 : current - 1); // 左へ→次、右へ→前
    }
  });

  container.addEventListener('pointercancel', function () {
    tracking = false;
  });

  show(0);
})();
