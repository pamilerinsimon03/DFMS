<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Maintenance - DFMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #01017A;
            --primary-light: rgba(1, 1, 122, 0.08);
            --accent: #FAA62C;
            --bg-dark: #0a0a23;
            --text-light: #f3f4f6;
            --glass-bg: rgba(255, 255, 255, 0.07);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at center, #11113b 0%, #030311 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: var(--text-light);
        }

        .maintenance-container {
            position: relative;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 48px 40px;
            width: 90%;
            max-width: 520px;
            text-align: center;
            box-sizing: border-box;
            z-index: 10;
            animation: containerFadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes containerFadeIn {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon-wrapper {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(250, 166, 44, 0.1);
            border-radius: 50%;
            border: 2px dashed var(--accent);
            animation: pulseGlow 3s infinite ease-in-out;
        }

        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(250, 166, 44, 0.2);
            }
            50% {
                box-shadow: 0 0 30px 10px rgba(250, 166, 44, 0.4);
            }
        }

        .maintenance-icon {
            font-size: 42px;
            animation: floatIcon 4s infinite ease-in-out;
        }

        @keyframes floatIcon {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(5deg); }
        }

        h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 16px 0;
            letter-spacing: -0.5px;
        }

        p {
            font-size: 15px;
            line-height: 1.6;
            color: #a0aec0;
            margin: 0 0 32px 0;
        }

        .progress-track {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 32px;
        }

        .progress-bar {
            height: 100%;
            width: 45%;
            background: linear-gradient(90deg, var(--accent) 0%, #ffc470 100%);
            border-radius: 10px;
            animation: slideProgress 2s infinite linear;
        }

        @keyframes slideProgress {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(250%); }
        }

        .meta-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding-top: 24px;
            font-size: 13px;
            color: #718096;
        }

        .meta-footer strong {
            color: var(--accent);
        }

        .btn-home {
            display: inline-block;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-top: 16px;
        }

        .btn-home:hover {
            border-color: #ffffff;
            background: rgba(255, 255, 255, 0.05);
        }

        /* Decorative Background Particles */
        .particle {
            position: absolute;
            background: rgba(250, 166, 44, 0.3);
            border-radius: 50%;
            z-index: 1;
            filter: blur(40px);
        }

        .p1 { width: 300px; height: 300px; top: -100px; left: -100px; background: rgba(1, 1, 122, 0.4); }
        .p2 { width: 250px; height: 250px; bottom: -80px; right: -50px; }
    </style>
</head>
<body>
    <div class="particle p1"></div>
    <div class="particle p2"></div>

    <div class="maintenance-container">
        <div class="icon-wrapper">
            <span class="maintenance-icon">🛠️</span>
        </div>
        <h1>System Under Maintenance</h1>
        <p>
            The Document File Management System (DFMS) is currently undergoing scheduled database upgrades and visual optimizations. 
            We should be back online shortly. Thank you for your patience!
        </p>
        <div class="progress-track">
            <div class="progress-bar"></div>
        </div>
        <div class="meta-footer">
            Estimated completion: <strong>Under 15 minutes</strong>
        </div>
    </div>
</body>
</html>
