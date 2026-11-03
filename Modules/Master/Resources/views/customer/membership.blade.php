<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In!Fruity Membership Cards</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: var(--erp-canvas);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 350px), 1fr));
            gap: 30px;
            max-width: 1200px;
            width: 100%;
        }

        .card {
            width: 100%;
            max-width: 380px;
            height: 240px;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
        }

        /* Bronze Card */
        .card.bronze {
            background: linear-gradient(135deg, #FF8C00 0%, #FF6B35 50%, #D2691E 100%);
        }

        /* Silver Card */
        .card.silver {
            background: linear-gradient(135deg, #C0C0C0 0%, #A8A8A8 50%, #808080 100%);
        }

        /* Gold Card */
        .card.gold {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FF8C00 100%);
        }

        /* Platinum Card */
        .card.platinum {
            background: linear-gradient(135deg, #40E0D0 0%, #48CAE4 50%, #0077B6 100%);
        }

        /* Abstract Background Patterns */
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.35;
            z-index: 1;
        }

        .bronze::before {
            background:
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.6) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.5) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 255, 255, 0.4) 0%, transparent 50%),
                linear-gradient(135deg, transparent 0%, rgba(255, 255, 255, 0.3) 50%, transparent 100%),
                conic-gradient(from 45deg at 70% 30%, transparent 0deg, rgba(255, 255, 255, 0.4) 90deg, transparent 180deg),
                linear-gradient(45deg, rgba(255, 140, 0, 0.3) 0%, transparent 50%);
            background-size: 200px 200px, 150px 150px, 100px 100px, 100% 100%, 80px 80px, 100% 100%;
            background-position: 0 0, 100% 100%, 50% 50%, 0 0, 80% 20%, 0 0;
        }

        .silver::before {
            background:
                radial-gradient(ellipse at 30% 70%, rgba(255, 255, 255, 0.7) 0%, transparent 60%),
                radial-gradient(ellipse at 70% 30%, rgba(255, 255, 255, 0.6) 0%, transparent 60%),
                linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.4) 50%, transparent 70%),
                linear-gradient(-45deg, transparent 40%, rgba(255, 255, 255, 0.3) 60%, transparent 80%),
                conic-gradient(from 90deg at 80% 80%, transparent 0deg, rgba(255, 255, 255, 0.5) 120deg, transparent 240deg),
                repeating-linear-gradient(45deg, transparent 0px, rgba(255, 255, 255, 0.2) 1px, transparent 2px, transparent 20px);
            background-size: 180px 180px, 120px 120px, 100% 100%, 100% 100%, 60px 60px, 40px 40px;
            background-position: 20% 20%, 80% 80%, 0 0, 0 0, 90% 90%, 0 0;
        }

        .gold::before {
            background:
                radial-gradient(circle at 25% 75%, rgba(255, 255, 255, 0.7) 0%, transparent 55%),
                radial-gradient(circle at 75% 25%, rgba(255, 255, 255, 0.6) 0%, transparent 55%),
                radial-gradient(circle at 50% 10%, rgba(255, 255, 255, 0.5) 0%, transparent 40%),
                linear-gradient(120deg, transparent 0%, rgba(255, 255, 255, 0.4) 30%, transparent 70%),
                conic-gradient(from 180deg at 30% 70%, transparent 0deg, rgba(255, 255, 255, 0.5) 60deg, transparent 120deg),
                repeating-conic-gradient(from 0deg at 85% 15%, transparent 0deg, rgba(255, 255, 255, 0.3) 30deg, transparent 60deg);
            background-size: 160px 160px, 140px 140px, 100px 100px, 100% 100%, 70px 70px, 50px 50px;
            background-position: 10% 10%, 90% 90%, 50% 0%, 0 0, 20% 80%, 90% 10%;
        }

        .platinum::before {
            background:
                radial-gradient(ellipse at 40% 60%, rgba(255, 255, 255, 0.8) 0%, transparent 65%),
                radial-gradient(ellipse at 60% 40%, rgba(255, 255, 255, 0.7) 0%, transparent 65%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.6) 0%, transparent 50%),
                linear-gradient(60deg, transparent 20%, rgba(255, 255, 255, 0.4) 50%, transparent 80%),
                conic-gradient(from 270deg at 20% 20%, transparent 0deg, rgba(255, 255, 255, 0.5) 90deg, transparent 180deg),
                repeating-linear-gradient(30deg, transparent 0px, rgba(255, 255, 255, 0.25) 1px, transparent 3px, transparent 25px);
            background-size: 200px 200px, 180px 180px, 120px 120px, 100% 100%, 90px 90px, 35px 35px;
            background-position: 0% 0%, 100% 100%, 85% 85%, 0 0, 10% 10%, 0 0;
        }

        .card-content {
            position: relative;
            z-index: 2;
            padding: 25px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: white;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .brand-name {
            font-size: 24px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .card-type {
            text-align: right;
        }

        .card-tier {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 2px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .card-subtitle {
            font-size: 12px;
            opacity: 0.9;
            font-weight: normal;
        }

        .card-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .member-label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .member-number {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .expire-section {
            text-align: right;
        }

        .expire-label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .expire-date {
            font-size: 18px;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .member-name {
            font-size: 18px;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .progress-bar {
            width: 120px;
            height: 8px;
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-handle {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            background-color: white;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .cards-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .card {
                width: 100%;
                max-width: 100%;
            max-width: 380px;
                margin: 0 auto;
            }
        }

        /* Additional Abstract Elements */
        .card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.25;
            z-index: 1;
            transition: opacity 0.3s ease;
        }

        .bronze::after {
            background:
                radial-gradient(circle at 90% 10%, rgba(255, 255, 255, 0.8) 0%, transparent 30%),
                radial-gradient(circle at 10% 90%, rgba(255, 255, 255, 0.7) 0%, transparent 35%),
                linear-gradient(225deg, transparent 60%, rgba(255, 255, 255, 0.5) 80%, transparent 100%),
                conic-gradient(from 315deg at 60% 40%, transparent 0deg, rgba(255, 255, 255, 0.4) 45deg, transparent 90deg),
                radial-gradient(circle at 65% 35%, rgba(255, 140, 0, 0.4) 0%, transparent 25%);
            background-size: 100px 100px, 80px 80px, 100% 100%, 120px 120px, 60px 60px;
            background-position: 95% 5%, 5% 95%, 0 0, 70% 30%, 65% 35%;
        }

        .silver::after {
            background:
                radial-gradient(circle at 85% 15%, rgba(255, 255, 255, 0.8) 0%, transparent 40%),
                radial-gradient(circle at 15% 85%, rgba(255, 255, 255, 0.7) 0%, transparent 35%),
                linear-gradient(135deg, transparent 70%, rgba(255, 255, 255, 0.5) 85%, transparent 100%),
                repeating-radial-gradient(circle at 50% 50%, transparent 0px, rgba(255, 255, 255, 0.3) 20px, transparent 40px),
                radial-gradient(circle at 30% 70%, rgba(192, 192, 192, 0.4) 0%, transparent 30%);
            background-size: 90px 90px, 70px 70px, 100% 100%, 80px 80px, 70px 70px;
            background-position: 90% 10%, 10% 90%, 0 0, 50% 50%, 30% 70%;
        }

        .gold::after {
            background:
                radial-gradient(circle at 95% 5%, rgba(255, 255, 255, 0.9) 0%, transparent 35%),
                radial-gradient(circle at 5% 95%, rgba(255, 255, 255, 0.8) 0%, transparent 40%),
                linear-gradient(315deg, transparent 65%, rgba(255, 255, 255, 0.6) 85%, transparent 100%),
                conic-gradient(from 45deg at 80% 20%, transparent 0deg, rgba(255, 255, 255, 0.5) 60deg, transparent 120deg),
                radial-gradient(circle at 25% 75%, rgba(255, 215, 0, 0.5) 0%, transparent 35%);
            background-size: 110px 110px, 90px 90px, 100% 100%, 100px 100px, 80px 80px;
            background-position: 98% 2%, 2% 98%, 0 0, 85% 15%, 25% 75%;
        }

        .platinum::after {
            background:
                radial-gradient(circle at 88% 12%, rgba(255, 255, 255, 0.9) 0%, transparent 38%),
                radial-gradient(circle at 12% 88%, rgba(255, 255, 255, 0.8) 0%, transparent 42%),
                linear-gradient(45deg, transparent 68%, rgba(255, 255, 255, 0.6) 88%, transparent 100%),
                repeating-conic-gradient(from 90deg at 25% 75%, transparent 0deg, rgba(255, 255, 255, 0.4) 20deg, transparent 40deg),
                radial-gradient(circle at 60% 40%, rgba(64, 224, 208, 0.5) 0%, transparent 40%);
            background-size: 95px 95px, 75px 75px, 100% 100%, 60px 60px, 90px 90px;
            background-position: 92% 8%, 8% 92%, 0 0, 20% 80%, 60% 40%;
        }

        .card:hover::after {
            opacity: 0.4;
        }

        /* Shine Effect */
        .card-shine {
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s ease;
            z-index: 3;
        }

        .card:hover .card-shine {
            left: 100%;
        }
    </style>
<link rel="stylesheet" href="{{ asset('assets/css/erp-theme.css') }}?v={{ filemtime(public_path('assets/css/erp-theme.css')) }}">
</head>

<body class="erp-ui">
    <div class="cards-container">
        <!-- Bronze Card -->
        <div class="card bronze">
            <div class="card-shine"></div>
            <div class="card-content">
                <div class="card-header">
                    <div class="brand-name">In!Fruity</div>
                    <div class="card-type">
                        <div class="card-tier">Bronze</div>
                        <div class="card-subtitle">Member Card</div>
                    </div>
                </div>

                <div class="card-details">
                    <div class="member-info">
                        <div class="member-label">nomor member</div>
                        <div class="member-number">9999 9999 9999 9999</div>
                    </div>
                    <div class="expire-section">
                        <div class="expire-label">expired date</div>
                        <div class="expire-date">12/25</div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="member-name">Achmad Noorman Setiawan</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 35%;"></div>
                        <div class="progress-handle"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Silver Card -->
        <div class="card silver">
            <div class="card-shine"></div>
            <div class="card-content">
                <div class="card-header">
                    <div class="brand-name">In!Fruity</div>
                    <div class="card-type">
                        <div class="card-tier">Silver</div>
                        <div class="card-subtitle">Member Card</div>
                    </div>
                </div>

                <div class="card-details">
                    <div class="member-info">
                        <div class="member-label">nomor member</div>
                        <div class="member-number">9999 9999 9999 9999</div>
                    </div>
                    <div class="expire-section">
                        <div class="expire-label">expired date</div>
                        <div class="expire-date">12/25</div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="member-name">Achmad Noorman Setiawan</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 55%;"></div>
                        <div class="progress-handle"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gold Card -->
        <div class="card gold">
            <div class="card-shine"></div>
            <div class="card-content">
                <div class="card-header">
                    <div class="brand-name">In!Fruity</div>
                    <div class="card-type">
                        <div class="card-tier">Gold</div>
                        <div class="card-subtitle">Member Card</div>
                    </div>
                </div>

                <div class="card-details">
                    <div class="member-info">
                        <div class="member-label">nomor member</div>
                        <div class="member-number">9999 9999 9999 9999</div>
                    </div>
                    <div class="expire-section">
                        <div class="expire-label">expired date</div>
                        <div class="expire-date">12/25</div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="member-name">Achmad Noorman Setiawan</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 75%;"></div>
                        <div class="progress-handle"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Platinum Card -->
        <div class="card platinum">
            <div class="card-shine"></div>
            <div class="card-content">
                <div class="card-header">
                    <div class="brand-name">In!Fruity</div>
                    <div class="card-type">
                        <div class="card-tier">Platinum</div>
                        <div class="card-subtitle">Member Card</div>
                    </div>
                </div>

                <div class="card-details">
                    <div class="member-info">
                        <div class="member-label">nomor member</div>
                        <div class="member-number">9999 9999 9999 9999</div>
                    </div>
                    <div class="expire-section">
                        <div class="expire-label">expired date</div>
                        <div class="expire-date">12/25</div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="member-name">Achmad Noorman Setiawan</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 90%;"></div>
                        <div class="progress-handle"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
