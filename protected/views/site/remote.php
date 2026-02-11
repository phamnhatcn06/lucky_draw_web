<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Lucky Draw Remote</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: url("/images/background.jpg") no-repeat center center fixed;
            background-size: cover;
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: sans-serif;
            overflow: hidden;
        }

        @keyframes pulse-gold {

            0% {
                box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.7);
            }

            70% {
                box-shadow: 0 0 0 20px rgba(255, 215, 0, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(255, 215, 0, 0);
            }
        }

        @keyframes glow-burst {
            0% {
                box-shadow: 0 0 50px rgba(255, 215, 0, 0.5);
            }

            50% {
                box-shadow: 0 0 100px rgba(255, 215, 0, 1), 0 0 200px rgba(255, 140, 0, 0.8);
            }

            100% {
                box-shadow: 0 0 50px rgba(255, 215, 0, 0.5);
            }
        }

        #btnSpin {
            width: 80vw;
            height: 80vw;
            max-width: 400px;
            max-height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, #ffd700, #ff8c00);
            border: 10px solid #fff;
            box-shadow: 0 0 50px rgba(255, 215, 0, 0.5);
            font-size: 3rem;
            font-weight: bold;
            color: #7a0012;
            cursor: pointer;
            outline: none;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            transition: all 0.2s;
            animation: pulse-gold 2s infinite;
        }

        #btnSpin:active {
            transform: scale(0.95);
        }

        /* Strong glow when clicking */
        .clicking {
            animation: glow-burst 0.5s ease-out forwards !important;
            transform: scale(0.95);
            filter: brightness(1.2);
        }

        #btnSpin:disabled {
            filter: grayscale(0.8);
            opacity: 0.7;
            cursor: not-allowed;
            animation: none;
        }

        .status {
            margin-top: 30px;
            font-size: 1.2rem;
            opacity: 0.7;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            font-weight: bold;
        }
    </style>
</head>

<body>

    <button id="btnSpin">QUAY</button>
    <div class="status" id="status">Sẵn sàng</div>

    <script>
        const btn = document.getElementById('btnSpin');
        const status = document.getElementById('status');
        const API_URL = '<?php echo Yii::app()->createAbsoluteUrl("api/remoteSpin"); ?>';

        btn.addEventListener('click', async () => {
            btn.disabled = true;
            status.textContent = "Đang gửi lệnh...";

            try {
                const res = await fetch(API_URL);
                const data = await res.json();
                if (data.ok) {
                    status.textContent = "Đã gửi lệnh QUAY!";
                    setTimeout(() => {
                        status.textContent = "Sẵn sàng";
                        btn.disabled = false;
                    }, 2000);
                } else {
                    status.textContent = "Lỗi gửi lệnh";
                    btn.disabled = false;
                }
            } catch (e) {
                status.textContent = "Lỗi kết nối";
                btn.disabled = false;
            }
        });
    </script>
</body>

</html>