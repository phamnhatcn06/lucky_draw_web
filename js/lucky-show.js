const API = window.__API;

const diceAnim = document.getElementById('diceAnim');
const prizeNameEl = document.getElementById('prizeName');
const remainingEl = document.getElementById('remaining');

const winnerBox = document.getElementById('winnerBox');
const bigCodeEl = document.getElementById('bigCode');
const fullNameEl = document.getElementById('fullName');
const departmentEl = document.getElementById('department');
const companyEl = document.getElementById('company');

// Audio
const audioRolling = new Audio('audio/rolling.mp3');
audioRolling.loop = true;
const audioWin = new Audio('audio/win.mp3');

let isSpinning = false;
// ===== 3D DICE SETUP =====
const diceWrap = document.getElementById('diceWrap');
const boardWrap = document.getElementById('board');
const cubes = [
    document.getElementById('c1'),
    document.getElementById('c2'),
    document.getElementById('c3'),
];
let prizeLocked = false;   // 🔒 đang khóa, không cho sang giải mới
const PIPS = {
    1: ['p5'],
    2: ['p1', 'p9'],
    3: ['p1', 'p5', 'p9'],
    4: ['p1', 'p3', 'p7', 'p9'],
    5: ['p1', 'p3', 'p5', 'p7', 'p9'],
    6: ['p1', 'p3', 'p4', 'p6', 'p7', 'p9'],
};
const FACE_NUM = { front: 1, back: 6, right: 3, left: 4, top: 2, bottom: 5 };
const FACE_ORDER = ['front', 'right', 'left', 'back', 'top', 'bottom'];

function makeFace(name, num) {
    const f = document.createElement('div');
    f.className = `face ${name}`;
    (PIPS[num] || []).forEach(p => {
        const dot = document.createElement('span');
        dot.className = `pip ${p}`;
        f.appendChild(dot);
    });
    return f;
}

function buildCube(el) {
    if (!el) return;
    el.innerHTML = '';
    FACE_ORDER.forEach(name => el.appendChild(makeFace(name, FACE_NUM[name])));
}

cubes.forEach(buildCube);

const diceState = cubes.map((el, i) => ({
    el,
    rx: -28 + i * 6,
    ry: 35 + i * 10,
    rz: 0,
    tx: 0, ty: 0,       // Translation x, y
    vx: 0, vy: 0, vz: 0, // Rotation velocity
    vtx: 0, vty: 0,      // Translation velocity
    spinning: false,
}));

function applyDice(d) {
    if (!d.el) return;
    // Add translation for "rolling" effect
    d.el.style.transform = `translate3d(${d.tx}px, ${d.ty}px, 0) rotateX(${d.rx}deg) rotateY(${d.ry}deg) rotateZ(${d.rz}deg)`;
}

// function randVel(){
//     const s = () => (Math.random() > 0.5 ? 1 : -1);
//     return {
//         vx: s() * (16 + Math.random()*30),
//         vy: s() * (18 + Math.random()*34),
//         vz: s() * (12 + Math.random()*26),
//     };
// }

function randVel() {
    const s = () => (Math.random() > 0.5 ? 1 : -1);
    return {
        vx: s() * (6 + Math.random() * 10),
        vy: s() * (8 + Math.random() * 14),
        vz: s() * (2 + Math.random() * 6),
    };
}

function startDice3D() {
    diceState.forEach(d => {
        const v = randVel();
        d.vx = v.vx;
        d.vy = v.vy;
        d.vz = v.vz;

        // Random tumbling velocity
        d.vtx = (Math.random() - 0.5) * 40; // Mạnh hơn
        d.vty = (Math.random() - 0.5) * 40;

        d.spinning = true;
    });
}

function stopDice3D() {
    diceState.forEach(d => d.spinning = false);
}

function diceRAF() {
    diceState.forEach(d => {
        if (d.spinning) {
            // ROTATION CHAOS
            if (Math.random() < 0.05) { // Frequent changes
                const v = randVel();
                d.vx = v.vx;
                d.vy = v.vy;
                d.vz = v.vz;
            }
            d.rx += d.vx;
            d.ry += d.vy;
            d.rz += d.vz;

            // TRANSLATION CHAOS (ROLLING)
            d.tx += d.vtx;
            d.ty += d.vty;

            // Boundary bounce (keep within ~100px range)
            const LIMIT = 80;
            if (d.tx > LIMIT) { d.tx = LIMIT; d.vtx *= -0.8; }
            if (d.tx < -LIMIT) { d.tx = -LIMIT; d.vtx *= -0.8; }
            if (d.ty > LIMIT) { d.ty = LIMIT; d.vty *= -0.8; }
            if (d.ty < -LIMIT) { d.ty = -LIMIT; d.vty *= -0.8; }

            // Random jostle
            if (Math.random() < 0.1) {
                d.vtx += (Math.random() - 0.5) * 10;
                d.vty += (Math.random() - 0.5) * 10;
            }
        } else {
            // Return to center slowly
            d.tx *= 0.85;
            d.ty *= 0.85;
            if (Math.abs(d.tx) < 0.5) d.tx = 0;
            if (Math.abs(d.ty) < 0.5) d.ty = 0;
        }
        applyDice(d);
    });
    requestAnimationFrame(diceRAF);
}

diceRAF();

// --- REMOTE CONTROL POLLING ---
// API URLs are already in window.__API (assigned to API const)

// Poll every 1s
setInterval(async () => {
    // Only poll if tab is visible to save resources? (Optional)
    if (document.hidden) return;

    // Don't spin if already spinning or locked
    if (isSpinning || prizeLocked) return;

    try {
        const res = await fetchJSON(API.checkRemote);
        if (res && res.ok && res.command === 'spin') {
            console.log("Remote spin command received!");
            // Check again to be safe
            if (!isSpinning && !prizeLocked) {
                // Trigger the spin
                // If spin() requires event, we might need to simulate or just call it
                // spin() expects 'e' but handles missing 'e' gracefully?
                // Looking at spin(e): if(e) e.preventDefault();
                // So calling spin() without args is fine.
                spin();
            }
        }
    } catch (e) {
        // console.error("Remote poll error", e); // Silence errors to avoid console spam
    }
}, 1000);

// show/hide/scale states
function diceHidden() {
    if (!diceWrap) return;
    diceWrap.classList.add('hidden');
    boardWrap.classList.add('hidden');
    diceWrap.classList.remove('isSpinning', 'isIdle');
}

function diceIdleSmall() {
    if (!diceWrap) return;
    diceWrap.classList.remove('hidden', 'isSpinning');
    boardWrap.classList.remove('hidden', 'isSpinning'); // Already removing?
    diceWrap.classList.add('isIdle');
}

function diceSpinningBig() {
    if (!diceWrap) return;
    diceWrap.classList.remove('hidden', 'isIdle');
    diceWrap.classList.add('isSpinning');
    boardWrap.classList.add('isSpinning'); // Add this line
}

// ---------- helpers ----------
const screenEl = document.querySelector('.screen');
const lightSweep = document.getElementById('lightSweep');

function flashScreen() {
    if (!screenEl) return;
    screenEl.classList.add('flash', 'shake');
    setTimeout(() => screenEl.classList.remove('flash', 'shake'), 350);
}

function sweepLight() {
    if (!lightSweep) return;
    lightSweep.classList.add('active');
    setTimeout(() => lightSweep.classList.remove('active'), 700);
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

async function fetchJSON(url, options) {
    const res = await fetch(url, Object.assign({ cache: 'no-store' }, options || {}));
    const json = await res.json().catch(() => null);
    if (!json) throw new Error('Invalid JSON');
    return json;
}

// Dice GIF controls
function showDice() {
    if (!diceAnim) return;
    diceAnim.classList.remove('hidden');
}

function hideDice() {
    if (!diceAnim) return;
    diceAnim.classList.add('hidden');
}

function startDice() {
    if (!diceAnim) return;

    // restart gif animation
    const src = diceAnim.getAttribute('src') || '';
    diceAnim.setAttribute('src', src.split('?')[0] + '?t=' + Date.now());

    showDice();
    diceAnim.classList.add('spinning');
}

function stopDice() {
    if (!diceAnim) return;
    diceAnim.classList.remove('spinning');
}

// ---------- fireworks canvas ----------
let fw = null;
if (window.ModernFireworks) {
    fw = new ModernFireworks('fxCanvas');
}

async function fireworksShow(prizeType = 'special') {
    if (!fw) return;
    fw.fireSequence(); // Initial burst
    await sleep(1000);
    fw.startContinuous(); // Keep going until popup is dismissed
}

// async function fireworksShow(){
//     if (!ctx || !canvas) return;
//
//     fxRunning = true;
//     particles = [];
//     ctx.clearRect(0,0,canvas.width,canvas.height);
//
//     const cx = canvas.width/2;
//     const cy = canvas.height*0.38;
//
//     spawnBurst(cx, cy);
//     spawnBurst(cx - canvas.width*0.18, cy + canvas.height*0.06);
//     spawnBurst(cx + canvas.width*0.18, cy + canvas.height*0.06);
//
//     stepFX();
//     await sleep(1200);
// }

// ---------- petals canvas ----------
const petalCanvas = document.getElementById('petalCanvas');
const pctx = petalCanvas ? petalCanvas.getContext('2d') : null;
let petals = [];
let petalRunning = false;

function resizePetal() {
    if (!petalCanvas) return;
    petalCanvas.width = window.innerWidth;
    petalCanvas.height = window.innerHeight;
}

window.addEventListener('resize', resizePetal);
resizePetal();

function spawnPetals() {
    petals = [];
    const count = 200; // Increased density (was 80)
    for (let i = 0; i < count; i++) {
        petals.push({
            x: Math.random() * (petalCanvas ? petalCanvas.width : window.innerWidth),
            y: -Math.random() * 300,
            vy: 3 + Math.random() * 4,
            vx: -0.8 + Math.random() * 1.6,
            size: 5 + Math.random() * 7, // Slightly bigger (5-12px)
            rot: Math.random() * Math.PI,
            vr: -0.03 + Math.random() * 0.06,
        });
    }
}

function drawPetals() {
    if (!petalRunning || !pctx || !petalCanvas) return;

    pctx.clearRect(0, 0, petalCanvas.width, petalCanvas.height);

    petals.forEach(p => {
        p.y += p.vy;
        p.x += p.vx;
        p.rot += p.vr;

        pctx.save();
        pctx.translate(p.x, p.y);
        pctx.rotate(p.rot);
        pctx.fillStyle = "rgba(255,223,0,.9)"; // Bright gold for stars

        // Draw star
        pctx.beginPath();
        const spikes = 5;
        const outerRadius = p.size;
        const innerRadius = p.size / 2;
        let rot = Math.PI / 2 * 3;
        let x = 0;
        let y = 0;
        const step = Math.PI / spikes;

        pctx.moveTo(0, 0 - outerRadius);
        for (let i = 0; i < spikes; i++) {
            x = Math.cos(rot) * outerRadius;
            y = Math.sin(rot) * outerRadius;
            pctx.lineTo(x, y);
            rot += step;

            x = Math.cos(rot) * innerRadius;
            y = Math.sin(rot) * innerRadius;
            pctx.lineTo(x, y);
            rot += step;
        }
        pctx.lineTo(0, 0 - outerRadius);
        pctx.closePath();
        pctx.fill();
        pctx.restore();
    });

    // Remove petals that have fallen off screen
    petals = petals.filter(p => p.y < petalCanvas.height + 40);

    // Continuously spawn new petals while running - More stars, faster
    if (petals.length < 150) { // Maintain higher density
        for (let i = 0; i < 15; i++) {
            petals.push({
                x: Math.random() * petalCanvas.width,
                y: -Math.random() * 50,
                vy: 3 + Math.random() * 4,
                vx: -0.8 + Math.random() * 1.6,
                size: 5 + Math.random() * 7, // Slightly bigger
                rot: Math.random() * Math.PI,
                vr: -0.03 + Math.random() * 0.06,
            });
        }
    }

    requestAnimationFrame(drawPetals);
}

function petalsShow() {
    if (!pctx || !petalCanvas) return;
    petalRunning = true;
    spawnPetals();
    drawPetals();
}

function stopPetals() {
    petalRunning = false;
    petals = [];
    if (pctx && petalCanvas) {
        pctx.clearRect(0, 0, petalCanvas.width, petalCanvas.height);
    }
}

// ---------- load header info ----------
async function refreshPrizeAndStatus() {
    try {
        const p = await fetchJSON(API.prize);
        if (p.ok && p.data) {
            window.currentPrizeData = p.data; // Store globally
            if (p.data.quantity == p.data.awarded) {
                prizeLocked = true;
                prizeNameEl.textContent = 'ĐÃ QUAY XONG GIẢI';
                document.getElementById('btnNextPrize').classList.add('active');
            } else {
                prizeLocked = false; // Unlock if spots opened up
                prizeNameEl.textContent = p.data.prize_name;
                document.getElementById('btnNextPrize').classList.remove('active');
            }
            // Update counter
            const counter = document.getElementById('prizeCounter');
            if (counter) {
                counter.textContent = `${p.data.awarded} / ${p.data.quantity}`;
            }
        }
        if (p.ok && !p.data) prizeNameEl.textContent = 'ĐÃ QUAY XONG';
        const s = await fetchJSON(API.status);
        if (s.ok) remainingEl.textContent = "Còn lại: " + s.data.remaining;
    } catch (e) {
        // ignore
    }
}

// ===== BOOM (confetti) =====
const boomCanvas = document.getElementById('boomCanvas');
const bctx = boomCanvas ? boomCanvas.getContext('2d') : null;
let booms = [];
let boomRun = false;

function resizeBoom() {
    if (!boomCanvas) return;
    boomCanvas.width = window.innerWidth;
    boomCanvas.height = window.innerHeight;
}

window.addEventListener('resize', resizeBoom);
resizeBoom();

function boomBurst() {
    if (!bctx || !boomCanvas) return;

    booms = [];
    boomRun = true;

    const cx = boomCanvas.width / 2;
    const cy = boomCanvas.height * 0.45;

    const count = 160;
    for (let i = 0; i < count; i++) {
        const a = Math.random() * Math.PI * 2;
        const sp = 3 + Math.random() * 10;
        booms.push({
            x: cx,
            y: cy,
            vx: Math.cos(a) * sp,
            vy: Math.sin(a) * sp - (2 + Math.random() * 3),
            g: 0.14,
            life: 50 + Math.random() * 40,
            s: 2 + Math.random() * 4,
            r: Math.random() * Math.PI,
            vr: (-0.2 + Math.random() * 0.4),
            // gold/white/red-ish
            col: (Math.random() < 0.33) ? '255,211,106' : (Math.random() < 0.5 ? '255,255,255' : '255,80,80')
        });
    }
    requestAnimationFrame(stepBoom);
}

let megaConfettiActive = false;
let megaLoopId = null;

function startMegaConfetti() {
    if (!bctx || !boomCanvas) return;
    // booms = [];
    boomRun = true;
    megaConfettiActive = true;

    // Start animation loop if not already running effectively
    // logic in stepBoom handles the loop, but we need to ensure it's kicked off
    if (booms.length === 0) requestAnimationFrame(stepBoom);

    function spawnLoop() {
        if (!megaConfettiActive) return;
        spawnMegaBatch();
        megaLoopId = setTimeout(spawnLoop, 200); // Continuous spawn every 200ms
    }
    spawnLoop();
}

function stopMegaConfetti() {
    megaConfettiActive = false;
    if (megaLoopId) clearTimeout(megaLoopId);
}

function spawnMegaBatch() {
    if (!bctx || !boomCanvas) return;
    const W = boomCanvas.width;
    const H = boomCanvas.height;
    const count = 50; // spawn per batch

    // Left side
    for (let i = 0; i < count; i++) {
        const x = -10;
        const y = H * 0.9 - (Math.random() * 50); // Lower, closer to bottom
        // Angle: Shoot mostly UP, slightly right. (-90 to -60 degrees)
        // -PI/2 is UP.
        const angle = -Math.PI / 2 + (Math.random() * 0.5);
        const sp = 18 + Math.random() * 15;
        booms.push(createConfettiParticle(x, y, sp, angle));
    }

    // Right side
    for (let i = 0; i < count; i++) {
        const x = W + 10;
        const y = H * 0.9 - (Math.random() * 50);
        // Angle: Shoot mostly UP, slightly left. (-120 to -90 degrees)
        const angle = -Math.PI / 2 - (Math.random() * 0.5);
        const sp = 18 + Math.random() * 15;
        booms.push(createConfettiParticle(x, y, sp, angle));
    }
}

function createConfettiParticle(x, y, sp, angle) {
    return {
        x: x,
        y: y,
        vx: Math.cos(angle) * sp,
        vy: Math.sin(angle) * sp,
        g: 0.25, // heavier gravity
        life: 100 + Math.random() * 60,
        s: 2 + Math.random() * 4,
        r: Math.random() * Math.PI,
        vr: (-0.2 + Math.random() * 0.4),
        col: getRandomColor()
    };
}

function getRandomColor() {
    const colors = [
        '255,0,0', '0,255,0', '0,0,255', '255,255,0', '0,255,255', '255,0,255', // basic
        '255,99,71', '144,238,144', '135,206,235', '255,215,0', // fancy
        '255,165,0', '128,0,128', '255,192,203', '64,224,208', // orange, purple, pink, turquoise
        '255,255,255' // white
    ];
    return colors[Math.floor(Math.random() * colors.length)];
}


function stepBoom() {
    if (!boomRun || !bctx || !boomCanvas) return;

    bctx.clearRect(0, 0, boomCanvas.width, boomCanvas.height);

    booms = booms.filter(p => p.life > 0);

    for (const p of booms) {
        p.life -= 1;
        p.vy += p.g;
        p.x += p.vx;
        p.y += p.vy;
        p.r += p.vr;

        const alpha = Math.max(0, p.life / 90);
        bctx.save();
        bctx.translate(p.x, p.y);
        bctx.rotate(p.r);
        bctx.fillStyle = `rgba(${p.col},${alpha})`;
        bctx.fillRect(-p.s, -p.s, p.s * 2, p.s * 2);
        bctx.restore();
    }

    if (booms.length === 0) {
        boomRun = false;
        bctx.clearRect(0, 0, boomCanvas.width, boomCanvas.height);
        return;
    }
    requestAnimationFrame(stepBoom);
}

// ===== WINNER POPUP =====
const winnerPopup = document.getElementById('winnerPopup');
const wpPrize = document.getElementById('wpPrize');
const wpCode = document.getElementById('wpCode');
const wpName = document.getElementById('wpName');
const wpDept = document.getElementById('wpDept');
const wpCompany = document.getElementById('wpCompany');

function hidePopup() {
    if (!winnerPopup) return;
    winnerPopup.classList.remove('show');
}

function showPopup(data) {
    if (!winnerPopup) return;
    wpPrize.textContent = data.prize.name;
    wpCode.textContent = data.winner.code;
    wpName.textContent = data.winner.full_name;
    wpDept.textContent = data.winner.department;
    wpCompany.textContent = data.winner.company;

    winnerPopup.classList.add('show');
}

function explodeDice() {
    if (!diceWrap) return;

    // flash
    if (screenEl) {
        screenEl.classList.add('flashBoom');
        setTimeout(() => screenEl.classList.remove('flashBoom'), 220);
    }

    // confetti burst
    boomBurst();

    // animate diceWrap explode then hide
    diceWrap.classList.add('explode');
    setTimeout(() => {
        diceWrap.classList.remove('explode');
        diceHidden();
    }, 560);
}

function showWinner(data) {
    document.getElementById('bigCode').textContent = data.code || '----';
    document.getElementById('fullName').textContent = data.full_name || '—';
    document.getElementById('department').textContent = data.department || '—';
    document.getElementById('company').textContent = data.company || '—';
    document.getElementById('winnerOverlay').classList.remove('hidden');
    const popup = document.getElementById('winnerPopup');
    popup.classList.remove('hidden');
    requestAnimationFrame(() => popup.classList.add('show'));

    // Check special prize for mega confetti
    const pCode = (window.currentPrizeData && window.currentPrizeData.code) ? window.currentPrizeData.code : '';
    if (pCode.toLowerCase() === 'special') {
        startMegaConfetti();
    }
}

function hideWinner() {
    if (document.activeElement && typeof document.activeElement.blur === 'function') {
        document.activeElement.blur();
    }
    stopMegaConfetti(); // Stop confetti loop
    document.getElementById('winnerOverlay').classList.add('hidden');
    const popup = document.getElementById('winnerPopup');
    popup.classList.remove('show');

    // Restore dice/board to idle state (so prize name is visible)
    diceIdleSmall();

    setTimeout(() => popup.classList.add('hidden'), 200);
}
function updateWinnerSides() {
    const left = document.getElementById('winnerListLeft');
    const right = document.getElementById('winnerListRight');
    const center = document.getElementById('winnerListCenter');

    if (left && left.children.length > 0) {
        document.getElementById('winnersLeft')?.classList.remove('hidden');
    } else {
        document.getElementById('winnersLeft')?.classList.add('hidden');
    }

    if (right && right.children.length > 0) {
        document.getElementById('winnersRight')?.classList.remove('hidden');
    } else {
        document.getElementById('winnersRight')?.classList.add('hidden');
    }

    if (center && center.children.length > 0) {
        document.getElementById('winnerCenter')?.classList.remove('hidden');
    } else {
        document.getElementById('winnerCenter')?.classList.add('hidden');
    }
}

// ---------- main spin ----------
let currentWinner = null;
let currentPrizeId = null;

async function spin() {
    if (isSpinning) return;
    isSpinning = true;
    const prize = await fetchJSON(API.prize);
    if (prize.data.finished) {
        prizeLocked = true;
        // alert('Giải này đã quay đủ, hãy bấm NEXT'); // Bỏ alert theo yêu cầu "không tác dụng"
        document.getElementById('btnNextPrize').classList.add('active'); // Ensure button is highlighted
        return;
    }
    hideWinner();

    // 1) hiện dice nhỏ trước (như “chuẩn bị”)
    diceIdleSmall();
    await sleep(80);

    // 2) phóng to + quay
    if (typeof flashScreen === 'function') flashScreen();
    diceSpinningBig(); // Add this call to trigger CSS class change
    startDice3D();

    try {
        const apiPromise = fetchJSON(API.spin, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        });

        // Reset trạng thái
        $('#winner-popup').hide();
        $('#fireworks-container').hide();
        $('.confetti-container').remove(); // Xóa confetti cũ

        // Play rolling sound
        audioRolling.currentTime = 0;
        audioRolling.play().catch(e => console.log('Audio autoplay blocked:', e));

        // Đợi API trả về để biết duration
        const res = await apiPromise;

        if (!res.ok) {
            if (spinCancelController) spinCancelController.abort();

            // Stop audio
            audioRolling.pause();
            audioWin.pause();

            // Lỗi -> dừng luôn
            stopDice3D();
            diceIdleSmall();
            await sleep(180);
            diceHidden();
            document.getElementById('specialMsg').classList.add('hidden');
            alert(res.error || 'Quay thất bại');
            await refreshPrizeAndStatus();
            return;
        }

        // Lấy duration từ API (nếu có), mặc định 3200ms
        const spinDuration = (res.data.prize && res.data.prize.duration) ? res.data.prize.duration : 3200;
        console.log('Spin duration:', spinDuration);

        // Show Special Message if code is 'special' - But delay it halfway
        const pCode = (res.data.prize && res.data.prize.code) ? res.data.prize.code : '';
        let specialMsgTimeout = null;

        if (pCode.toLowerCase() === 'special') {
            const delayTime = spinDuration / 2;
            specialMsgTimeout = setTimeout(() => {
                document.getElementById('specialMsg').classList.remove('hidden');
            }, delayTime);
        }
        console.log('Spin duration:', spinDuration);

        // Đợi nốt phần thời gian còn lại (trừ đi thời gian đã chờ request)
        // Tuy nhiên để đơn giản và mượt, ta cứ chờ đủ duration tính từ lúc bắt đầu quay
        // Hoặc đơn giản là: sau khi có kết quả, chờ thêm (duration) rồi mới dừng.
        // Cách tốt nhất: song song request và sleep(minimum), sau đó check duration.

        // Ở đây ta làm cách đơn giản: Chờ request xong, lấy duration, rồi wait thêm một chút hoặc wait cho đủ tổng duration.
        // Nhưng vì request có thể nhanh/chậm, ta nên wait một khoảng cố định + duration của giải.

        // Logic mới:
        // 1. Start quay
        // 2. Gọi API
        // 3. API trả về duration (VD: 5000ms)
        // 4. Đảm bảo tổng thời gian quay >= duration.

        // await sleep(3200); // <-- BỎ dòng này đi vì ta sẽ wait động

        // Tính toán thời gian cần wait thêm
        // Giả sử request mất 200ms, duration = 5000ms -> wait thêm 4800ms.
        // Nhưng ta không đo chính xác thời gian request ở đây start/end.
        // Cách đơn giản nhất: Chờ request xong -> wait duration (chính xác là wait duration từ lúc request xong cũng được, hoặc hard set).

        // Để đúng ý "mỗi giải thời gian khác nhau", ta sẽ sleep theo duration của giải đó.
        await sleep(spinDuration);

        // --- KẾT THÚC QUAY (Success) ---
        audioRolling.pause();
        audioWin.currentTime = 0;
        audioWin.play().catch(e => console.log('Audio autoplay blocked:', e));

        stopDice3D();
        document.getElementById('specialMsg').classList.add('hidden'); // Hide message
        if (specialMsgTimeout) clearTimeout(specialMsgTimeout); // Clear timeout just in case
        diceIdleSmall();
        await sleep(180);
        diceHidden();
        if (!res.ok) {
            alert(res.error || 'Quay thất bại');
            await refreshPrizeAndStatus();
            return;
        }

        // 4) pháo hoa + petals + sweep
        await fireworksShow(res.data.prize.code);

        // Only show stars (petals) if NOT special prize
        const prizeCode = (res.data.prize && res.data.prize.code) ? res.data.prize.code.toLowerCase() : '';
        if (typeof petalsShow === 'function' && prizeCode !== 'special') {
            petalsShow();
        }

        if (typeof sweepLight === 'function') sweepLight();
        await sleep(250);

        // 5) show winner
        currentWinner = res.data.winner;
        currentPrizeId = res.data.prize.id;
        showWinner(res.data.winner);
        await refreshPrizeAndStatus();

    } catch (e) {
        diceHidden();
        alert('Lỗi hệ thống: ' + (e.message || e));
        console.error(e);
    } finally {
        isSpinning = false;
    }
}

// Assuming API object is defined somewhere above, e.g.:
// const API = {
//     prize: baseUrl + '/api/prize',
//     spin: baseUrl + '/api/spin',
//     confirm: baseUrl + '/api/confirm',
//     cancel: baseUrl + '/api/cancel',
// };
// We need to add checkAuth to it. For this example, I'll assume it's defined right before the global variables.
// If API object is not defined, this would be an error.
// For the purpose of this edit, I will insert the new API endpoint definition here,
// assuming the original API object definition ends with 'cancel' and a comma.
// If the API object is defined in a different file or structure, this placement might need adjustment.
// Given the instruction, I'll place it as if it's part of the API object definition.
// Since I don't have the full API object, I'll assume it's part of a larger definition.
// The provided snippet shows `cancel: baseUrl + '/api/cancel', checkAuth: baseUrl + '/api/checkAuth' };`
// This implies modifying an existing API object. I will place the new functions and call before the global variables.

// --- AUTHENTICATION ---
const AUTH_COOKIE = 'lucky_auth_token';
const AUTH_DURATION_HOURS = 3;

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function setCookie(name, value, hours) {
    const d = new Date();
    d.setTime(d.getTime() + (hours * 60 * 60 * 1000));
    const expires = "expires=" + d.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

async function checkAccess() {
    const token = getCookie(AUTH_COOKIE);
    if (!token) {
        // Block UI and ask for code
        const { value: code } = await Swal.fire({
            title: 'Nhập MÃ KÍCH HOẠT',
            input: 'password',
            inputLabel: 'Mã bảo mật để truy cập',
            inputPlaceholder: 'Nhập mã code...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            confirmButtonText: 'Truy cập',
            preConfirm: async (code) => {
                if (!code) {
                    Swal.showValidationMessage('Vui lòng nhập mã');
                    return false;
                }
                try {
                    // Assuming API object is accessible globally
                    const res = await fetchJSON(API.checkAuth, {
                        method: 'POST',
                        body: JSON.stringify({ code: code })
                    });
                    if (!res.ok) {
                        Swal.showValidationMessage(res.msg || 'Mã không đúng');
                        return false;
                    }
                    return true;
                } catch (e) {
                    Swal.showValidationMessage('Lỗi kết nối: ' + e);
                    return false;
                }
            }
        });

        if (code) {
            setCookie(AUTH_COOKIE, 'granted', AUTH_DURATION_HOURS);
            Swal.fire({
                icon: 'success',
                title: 'Truy cập thành công!',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            // Auto reload logic after expiration
            scheduleReload();
        }
    } else {
        // Already authenticated, schedule reload based on remaining time?
        // Simple approach: just schedule for the full duration from now (or slightly less if we stored timestamp).
        // Since we don't know when the cookie started without parsing it or storing extra data, 
        // we'll just check periodically or set a safe timeout.
        // User said "Nếu hết hạn thì tự động reload".
        // We can just rely on the next checkAccess validation?
        // But if the page isn't refreshed, JS doesn't know.
        // Let's set a timer for 3 hours from NOW to be safe, or check cookie existence every minute.
        setInterval(checkCookieExpiry, 60000); // Check every minute
    }
}

function checkCookieExpiry() {
    if (!getCookie(AUTH_COOKIE)) {
        location.reload(); // Reload to trigger auth popup again
    }
}

function scheduleReload() {
    setTimeout(() => {
        location.reload();
    }, AUTH_DURATION_HOURS * 60 * 60 * 1000);
}

// Start Auth Check
checkAccess();

// isSpinning is already declared elsewhere

async function confirmWinner() {
    stopPetals(); // Stop falling stars
    if (fw) fw.stopContinuous(); // Stop fireworks
    await fetchJSON(API.confirm, {
        method: 'POST',
        body: JSON.stringify({
            ...currentWinner,
            prize_id: currentPrizeId
        })
    });
    // 👉 lấy dữ liệu từ biến global (chứa ID đầy đủ)
    const winner = { ...currentWinner };

    // ✅ ĐẨY SANG CÁNH GÀ
    addWinnerToSide(winner);
    updateWinnerSides();
    hideWinner();

    // TODO: gọi API confirm nếu cần
    console.log('Đã xác nhận kết quả');
    await refreshPrizeAndStatus();
}

async function cancelWinner() {
    // Confirm before cancelling the current winner popup
    const result = await Swal.fire({
        title: 'Bạn có chắc chắn muốn huỷ?',
        text: "Kết quả này sẽ không được ghi nhận!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Đúng, huỷ kết quả!',
        cancelButtonText: 'Không, giữ lại'
    });

    if (!result.isConfirmed) return;

    stopPetals(); // Stop falling stars
    stopMegaConfetti(); // Ensure special confetti stops
    if (fw) fw.stopContinuous(); // Stop fireworks

    // Call API to cancel the winner (delete from DB)
    if (currentWinner && currentWinner.id) {
        try {
            const res = await fetchJSON(API.cancel, {
                method: 'POST',
                body: JSON.stringify({
                    participant_id: currentWinner.id, // Or depends on API expectation
                    prize_id: currentPrizeId
                })
            });
            if (!res.ok) console.error("Failed to cancel winner:", res);
        } catch (e) {
            console.error("Error cancelling winner:", e);
        }
    }

    hideWinner();
    await refreshPrizeAndStatus();
}



// SPACE = spin
document.addEventListener('keydown', function (e) {
    const isSpace = (e.code === 'Space' || e.keyCode === 32);
    const isEnter = (e.code === 'Enter' || e.keyCode === 13);

    // Check if popup is open
    const popup = document.getElementById('winnerPopup');
    const isPopupVisible = popup && !popup.classList.contains('hidden');

    // Enter confirms winner if popup is open
    if (isEnter && isPopupVisible) {
        e.preventDefault();
        confirmWinner();
        return;
    }

    if (!isSpace) return;

    if (isPopupVisible) {
        e.preventDefault();
        return;
    }

    if (prizeLocked) {
        return;
    }

    e.preventDefault();
    spin();
});

// ✅ Ẩn dice ngay từ đầu
// ✅ Ẩn dice ngay từ đầu nhưng hiện board (để hiện tên giải)
// hideDice(); // Don't hide completely, use idle state
diceIdleSmall(); // Show board and small dice
refreshPrizeAndStatus();

let sideToggle = false;

function addWinnerToSide(winner) {
    const li = document.createElement('li');
    li.innerHTML = `
         <span class="numberBlock">
            ${winner.code}
         </span>
         <span class="partInfo">
            <span class="partname">${winner.full_name}</span>
            <span class="job">${winner.department || ''}</span>
         </span>
         <button class="btn-delete" data-id="${winner.id || winner.participant_id}" data-prize="${currentPrizeId}" title="Xoá người này">❌</button>
    `;

    // Event handled by delegation


    // Check if single winner mode (quantity == 1)
    const isSingleWinner = window.currentPrizeData && window.currentPrizeData.quantity == 1;

    if (isSingleWinner) {
        // Render to center
        const centerList = document.getElementById('winnerListCenter');
        // Add special markup for center
        li.innerHTML = `
            <span class="center-content">
                <span class="numberBlock">${winner.code}</span>
                 <div class="info-block">
                    <span class="partname">${winner.full_name}</span>
                    <span class="job">${winner.department || ''}</span>
                 </div>
            </span>
             <button class="btn-delete" data-id="${winner.id || winner.participant_id}" data-prize="${currentPrizeId}" title="Xoá người này">🗑</button>
         `;

        if (centerList) {
            centerList.innerHTML = ''; // Ensure only one if logic requires, but prepend is fine too
            centerList.prepend(li);
            document.getElementById('winnerCenter').classList.remove('hidden');
            // Hide sides just in case
            document.getElementById('winnersLeft').classList.add('hidden');
            document.getElementById('winnersRight').classList.add('hidden');
        }
        return;
    }

    if (!sideToggle) {
        document.getElementById('winnerListLeft').prepend(li);
    } else {
        document.getElementById('winnerListRight').prepend(li);
    }
    sideToggle = !sideToggle;
}

// Event Delegation for Delete Buttons
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('btn-delete')) {
        const btn = e.target;
        const li = btn.closest('li');

        const id = btn.getAttribute('data-id');
        const prizeId = btn.getAttribute('data-prize');
        const namePart = li.querySelector('.partname');
        const name = namePart ? namePart.textContent : '---';

        handleRemoveWinnerClick(id, prizeId, name, li);
    }
});

async function handleRemoveWinnerClick(id, prizeId, name, li) {
    if (!id) {
        console.error('Missing ID');
        return;
    }

    const result = await Swal.fire({
        title: 'Xoá người trúng giải?',
        text: `Bạn có chắc muốn huỷ kết quả của: ${name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Xoá ngay',
        cancelButtonText: 'Thôi'
    });

    if (!result.isConfirmed) return;

    try {
        const res = await fetchJSON(API.cancel, {
            method: 'POST',
            body: JSON.stringify({
                id: id,
                prize_id: prizeId || currentPrizeId
            })
        });

        if (res.ok) {
            li.remove();
            // Refetch status to update counter and "Next" button state
            await refreshPrizeAndStatus();

            // Show success alert
            Swal.fire(
                'Đã xoá!',
                'Người trúng giải đã bị xoá khỏi danh sách.',
                'success'
            );
        } else {
            Swal.fire('Lỗi', res.msg || 'Không thể xoá', 'error');
        }
    } catch (e) {
        Swal.fire('Lỗi', 'Lỗi hệ thống khi xoá', 'error');
        console.error(e);
    }
}
async function nextPrize() {
    const res = await fetchJSON(API.nextPrize, { method: 'POST' });
    if (!res.ok) {
        alert(res.msg);
        return;
    }
    location.reload();
}
function nextPrizeManual() {
    prizeLocked = false;
    nextPrize(); // hàm cũ của bạn
}

document.querySelectorAll('.winners-side')
    .forEach(el => el.classList.add('show'));
document.getElementById('btnNextPrize').onclick = () => {
    if (prizeLocked) {
        nextPrizeManual();
    }
};

