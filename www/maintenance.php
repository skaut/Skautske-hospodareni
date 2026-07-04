<?php

declare(strict_types=1);

header('HTTP/1.1 503 Service Unavailable');
header('Retry-After: 300');

$startedAtLabel = $maintenance['startedAtLabel'] ?? 'čtvrtek 2. 7. 2026 od 20:00';
$startedAtDatetime = $maintenance['startedAtDatetime'] ?? '2026-07-02T20:00:00+02:00';
$endsAtLabel = $maintenance['endsAtLabel'] ?? 'nejpozději do 7. 7. 2026';
$endsAtDatetime = $maintenance['endsAtDatetime'] ?? '2026-07-07T23:59:59+02:00';

?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Skautské hospodaření online</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f5f1e8;
            --panel: #fffdf8;
            --text: #1f2a2a;
            --muted: #63706d;
            --primary: #263d3a;
            --primary-soft: #dce8e2;
            --accent: #f2b84b;
            --accent-soft: #fff3d4;
            --line: rgba(31, 42, 42, .14);
            --shadow: 0 24px 70px rgba(38, 61, 58, .18);
            --radius-lg: 32px;
            --radius-md: 18px;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            color: var(--text);
            background:
                radial-gradient(circle at 12% 14%, rgba(242, 184, 75, .32), transparent 30rem),
                radial-gradient(circle at 88% 8%, rgba(38, 61, 58, .16), transparent 32rem),
                linear-gradient(135deg, #f7f1e6 0%, #e8efeb 100%);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        main {
            width: min(960px, 100%);
        }

        .card {
            position: relative;
            overflow: hidden;
            background: rgba(255, 253, 248, .9);
            border: 1px solid rgba(255, 255, 255, .82);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }

        .card::before,
        .card::after {
            content: "";
            position: absolute;
            width: 360px;
            height: 360px;
            border-radius: 43% 57% 52% 48%;
            opacity: .16;
            pointer-events: none;
        }

        .card::before {
            top: -180px;
            right: -120px;
            background: var(--accent);
            transform: rotate(18deg);
        }

        .card::after {
            bottom: -210px;
            left: -150px;
            background: var(--primary);
            transform: rotate(-14deg);
        }

        .content {
            position: relative;
            z-index: 1;
            padding: clamp(28px, 5vw, 56px);
        }

        .topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: clamp(36px, 7vw, 70px);
        }

        .site-name {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: var(--primary);
            font-size: clamp(22px, 3vw, 31px);
            font-weight: 850;
            letter-spacing: -.035em;
            line-height: 1.1;
            text-decoration: none;
        }

        .site-mark {
            width: 42px;
            height: 42px;
            border-radius: 15px;
            background: var(--primary-soft);
            border: 1px solid rgba(38, 61, 58, .18);
            display: grid;
            place-items: center;
            color: var(--primary);
            font-size: 21px;
            font-weight: 900;
            letter-spacing: -.08em;
        }

        .official-logo {
            flex: 0 0 auto;
            width: clamp(78px, 12vw, 118px);
        }

        .official-logo img {
            display: block;
            width: 100%;
            height: auto;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            margin-bottom: 22px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: #765214;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .status::before {
            content: "";
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 0 6px rgba(242, 184, 75, .22);
        }

        h1 {
            margin: 0;
            max-width: 820px;
            color: var(--primary);
            font-size: clamp(38px, 7.4vw, 72px);
            line-height: .98;
            letter-spacing: -.05em;
        }

        .lead {
            max-width: 720px;
            margin: 26px 0 0;
            color: var(--muted);
            font-size: clamp(18px, 2.4vw, 23px);
            line-height: 1.55;
        }

        .time-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin: clamp(28px, 5vw, 46px) 0;
        }

        .time-box {
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, .62);
        }

        .time-box strong {
            display: block;
            margin-bottom: 8px;
            color: var(--primary);
            font-size: 14px;
            line-height: 1.3;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .time-box time {
            display: block;
            color: var(--text);
            font-size: clamp(22px, 3vw, 32px);
            font-weight: 850;
            line-height: 1.15;
        }

        .message {
            max-width: 780px;
            padding: 24px;
            border-left: 6px solid var(--accent);
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
            background: rgba(255, 255, 255, .66);
            color: var(--text);
            font-size: 18px;
            line-height: 1.65;
        }

        .message p {
            margin: 0;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            margin-top: 34px;
            padding-top: 22px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 15px;
            line-height: 1.45;
        }

        .footer strong {
            color: var(--primary);
        }

        @media (max-width: 680px) {
            body {
                padding: 14px;
            }

            .content {
                padding: 28px 22px;
            }

            .topbar {
                flex-direction: column;
                margin-bottom: 40px;
            }

            .official-logo {
                width: 88px;
            }

            .time-grid {
                grid-template-columns: 1fr;
            }

            .message {
                padding: 20px;
            }

            .footer {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <main aria-labelledby="page-title">
        <section class="card">
            <div class="content">
                <header class="topbar" aria-label="Hlavička stránky">
                    <div class="site-name" aria-label="h.skauting.cz">
                        <span class="site-mark" aria-hidden="true">H</span>
                        <span>h.skauting.cz</span>
                    </div>

                    <div class="official-logo">
                        <img src="/images/skaut-logo.svg" alt="Junák - český skaut">
                    </div>
                </header>

                <div class="status">Plánovaná odstávka</div>

                <h1 id="page-title">Aktualizujeme systém H.skauting</h1>

                <p class="lead">
                    Web h.skauting.cz je dočasně nedostupný z důvodu plánované aktualizace systému.
                </p>

                <div class="time-grid" aria-label="Čas odstávky">
                    <div class="time-box">
                        <strong>Začátek odstávky</strong>
                        <time datetime="<?= htmlspecialchars((string) $startedAtDatetime, ENT_QUOTES) ?>"><?= htmlspecialchars((string) $startedAtLabel) ?></time>
                    </div>
                    <div class="time-box">
                        <strong>Nejzazší konec</strong>
                        <time datetime="<?= htmlspecialchars((string) $endsAtDatetime, ENT_QUOTES) ?>"><?= htmlspecialchars((string) $endsAtLabel) ?></time>
                    </div>
                </div>

                <div class="message">
                    <p>
                        Čas odstávky se budeme snažit minimalizovat. Za případné komplikace se omlouváme
                        a děkujeme za pochopení.
                    </p>
                </div>

                <footer class="footer">
                    <div><strong>h.skauting.cz</strong></div>
                    <div>Probíhá aktualizace systému.</div>
                </footer>
            </div>
        </section>
    </main>
</body>
</html>
<?php

exit;
