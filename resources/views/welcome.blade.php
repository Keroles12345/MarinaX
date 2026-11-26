<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f6fa">
    <meta name="description" content="MarinaX — a platform for managing travel companies, bookings, operations, teams and reporting.">
    <title>{{ app()->getLocale() === 'ar' ? 'MarinaX | منصة إدارة وتشغيل الشركات السياحية' : 'MarinaX | Travel Operations Platform' }}</title>
    <script>
        (() => {
            const savedTheme = window.localStorage.getItem('marinax-theme');
            document.documentElement.dataset.theme = savedTheme === 'dark' ? 'dark' : 'light';
        })();
    </script>
    <style>

        :root {
            color-scheme: light;
            --ink: #1f2937;
            --muted: #64748b;
            --navy: #0d1220;
            --navy-light: #151f36;
            --teal: #2dd4bf;
            --teal-dark: #087f8c;
            --gold: #d9a441;
            --paper: #f4f6fa;
            --line: #dbe2ec;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            color: var(--ink);
            background: #fff;
            font-family: "Tajawal", "Segoe UI", Tahoma, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        a:focus-visible { outline: 3px solid var(--gold); outline-offset: 4px; }
        .shell { width: min(1160px, calc(100% - 48px)); margin-inline: auto; }
        .topbar { position: relative; z-index: 2; background: var(--navy); color: #fff; }
        .nav {
            min-height: 82px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        .brand { display: inline-flex; align-items: center; gap: 12px; }
        .brand-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 14px;
            color: var(--teal);
            background: rgba(24,182,164,.12);
        }
        .brand-mark svg { width: 25px; height: 25px; }
        .brand-name { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
        .brand-name span { color: var(--teal); }
        .nav-links { display: flex; align-items: center; gap: 25px; color: #c3d1d6; font-size: 13px; }
        .nav-links a { transition: color .2s ease; }
        .nav-links a:hover { color: #fff; }
        .nav-login {
            padding: 11px 19px;
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 10px;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            transition: background .2s ease, border-color .2s ease;
        }
        .nav-login:hover { border-color: var(--teal); background: rgba(24,182,164,.12); }

        .hero {
            position: relative;
            overflow: hidden;
            color: #fff;
            background:
                radial-gradient(ellipse at 82% 18%, rgba(45,212,191,.14), transparent 38%),
                linear-gradient(125deg, var(--navy) 8%, #111a2e 62%, var(--navy-light));
        }
        .hero::after {
            position: absolute;
            inset-inline-start: -180px;
            bottom: -310px;
            width: 600px;
            height: 600px;
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 50%;
            box-shadow: 0 0 0 56px rgba(255,255,255,.018), 0 0 0 112px rgba(255,255,255,.014);
            content: "";
            pointer-events: none;
        }
        .hero-grid {
            position: relative;
            z-index: 1;
            min-height: 610px;
            display: grid;
            grid-template-columns: 1fr 1.02fr;
            align-items: center;
            gap: 62px;
            padding-block: 74px 84px;
        }
        .hero-copy { max-width: 520px; }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #8de0d4;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .2px;
        }
        .eyebrow::before {
            width: 24px;
            height: 1px;
            background: var(--teal);
            content: "";
        }
        h1 {
            margin: 21px 0 18px;
            font-size: clamp(38px, 5vw, 58px);
            font-weight: 800;
            line-height: 1.28;
            letter-spacing: -.8px;
        }
        h1 span { color: var(--gold); }
        .hero-copy > p {
            max-width: 480px;
            margin: 0;
            color: #c2d0d5;
            font-size: 17px;
            line-height: 2;
        }
        .hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 13px; margin-top: 30px; }
        .button {
            min-height: 49px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 0 20px;
            border: 1px solid transparent;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 800;
            transition: transform .2s ease, background .2s ease, border-color .2s ease;
        }
        .button:hover { transform: translateY(-2px); }
        .button-primary { color: #161006; background: var(--gold); }
        .button-primary:hover { background: #e8b64e; }
        .button-outline { border-color: rgba(255,255,255,.24); color: #fff; }
        .button-outline:hover { border-color: rgba(255,255,255,.6); background: rgba(255,255,255,.06); }
        .button svg { width: 17px; height: 17px; }
        .hero-note { display: flex; align-items: center; gap: 8px; margin-top: 21px; color: #93aab3; font-size: 12px; }
        .hero-note svg { width: 15px; height: 15px; color: #58cdbb; }

        .preview-wrap { position: relative; padding: 20px 10px 24px 0; }
        .preview-glow {
            position: absolute;
            inset: 12% 9% 9% 3%;
            border-radius: 50%;
            background: rgba(45,212,191,.18);
            filter: blur(65px);
        }
        .preview {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 19px;
            background: #f9fbfa;
            box-shadow: 0 30px 75px rgba(0,0,0,.32);
            transform: perspective(1200px) rotateY(-4deg) rotateX(1deg);
        }
        .preview-head {
            min-height: 57px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 19px;
            border-bottom: 1px solid #e8efed;
            color: #173747;
        }
        .preview-brand { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 800; }
        .preview-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--teal); box-shadow: 0 0 0 4px #e0f5f1; }
        .preview-head span:last-child { color: #788c94; font-size: 10px; }
        .preview-body { display: grid; grid-template-columns: 1fr 54px; min-height: 330px; }
        .preview-content { padding: 21px 20px 18px; }
        .preview-title { margin: 0; color: #173747; font-size: 15px; font-weight: 800; }
        .preview-subtitle { margin: 6px 0 16px; color: #82939a; font-size: 10px; }
        .metric-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 9px; }
        .metric {
            min-height: 74px;
            padding: 11px 10px;
            border: 1px solid #e8efed;
            border-radius: 10px;
            background: #fff;
        }
        .metric-label { display: block; color: #84949a; font-size: 9px; }
        .metric strong { display: block; margin-top: 9px; color: #15384a; font-size: 14px; }
        .metric strong.teal { color: var(--teal-dark); }
        .preview-section-head { display: flex; align-items: center; justify-content: space-between; margin: 20px 0 9px; color: #193b4a; font-size: 10px; font-weight: 800; }
        .preview-section-head span:last-child { color: #16988d; font-size: 9px; }
        .trip-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            align-items: center;
            gap: 10px;
            padding: 10px 9px;
            border-top: 1px solid #edf1f0;
            color: #536b75;
            font-size: 9px;
        }
        .trip-name { color: #244453; font-size: 10px; font-weight: 700; }
        .trip-meta { display: block; margin-top: 4px; color: #91a0a5; font-size: 8px; }
        .trip-status { padding: 5px 7px; border-radius: 20px; color: #128274; background: #e7f7f3; font-size: 8px; }
        .preview-sidebar {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            padding-top: 20px;
            border-inline-start: 1px solid #e8efed;
            background: #f4f8f7;
        }
        .side-icon {
            width: 23px;
            height: 23px;
            display: grid;
            place-items: center;
            border-radius: 7px;
            color: #729099;
            font-size: 11px;
        }
        .side-icon.active { color: #087c72; background: #dbf3ee; }
        .preview-caption {
            position: absolute;
            inset-inline-end: -7px;
            bottom: -3px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            border: 1px solid rgba(255,255,255,.24);
            border-radius: 12px;
            color: #fff;
            background: #123c49;
            box-shadow: 0 12px 25px rgba(0,0,0,.2);
        }
        .caption-icon { width: 31px; height: 31px; display: grid; place-items: center; border-radius: 9px; color: #8ee4d7; background: rgba(24,182,164,.16); }
        .caption-icon svg { width: 17px; height: 17px; }
        .preview-caption strong { display: block; font-size: 11px; }
        .preview-caption small { display: block; margin-top: 4px; color: #b6c8cc; font-size: 9px; }

        .trust-strip { border-bottom: 1px solid var(--line); background: #fff; }
        .trust-inner { min-height: 104px; display: flex; align-items: center; justify-content: space-between; gap: 28px; }
        .trust-lead { max-width: 220px; color: #58717b; font-size: 13px; font-weight: 700; line-height: 1.8; }
        .trust-item { display: flex; align-items: center; gap: 11px; color: #244452; font-size: 13px; font-weight: 700; }
        .trust-icon { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 11px; color: #0b9487; background: #e9f8f5; }
        .trust-icon svg { width: 19px; height: 19px; }

        .section { padding-block: 96px; }
        .section-heading { max-width: 620px; margin: 0 auto 44px; text-align: center; }
        .section-kicker { color: var(--teal-dark); font-size: 12px; font-weight: 800; }
        h2 { margin: 12px 0 13px; color: #102e3d; font-size: clamp(27px, 3.4vw, 38px); line-height: 1.4; }
        .section-heading p { margin: 0; color: var(--muted); font-size: 15px; line-height: 1.9; }
        .feature-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 17px; }
        .feature {
            min-height: 220px;
            padding: 25px 22px;
            border: 1px solid var(--line);
            border-radius: 15px;
            background: #fff;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .feature:hover { transform: translateY(-4px); border-color: #c6e7e1; box-shadow: 0 18px 35px rgba(18,55,67,.07); }
        .feature-icon { width: 45px; height: 45px; display: grid; place-items: center; border-radius: 13px; color: #0d9185; background: #e8f7f4; }
        .feature-icon svg { width: 22px; height: 22px; }
        .feature h3 { margin: 19px 0 9px; color: #183b4a; font-size: 16px; }
        .feature p { margin: 0; color: #70838b; font-size: 13px; line-height: 1.9; }

        .workflow { padding-block: 5px 100px; }
        .workflow-panel {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: .9fr 1.1fr;
            align-items: center;
            gap: 55px;
            padding: 58px 62px;
            border-radius: 20px;
            color: #fff;
            background: linear-gradient(120deg, var(--navy-light), var(--navy));
        }
        .workflow-panel::before {
            position: absolute;
            inset-inline-end: -110px;
            top: -190px;
            width: 390px;
            height: 390px;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 50%;
            box-shadow: 0 0 0 45px rgba(255,255,255,.025), 0 0 0 90px rgba(255,255,255,.018);
            content: "";
        }
        .workflow-copy, .workflow-list { position: relative; z-index: 1; }
        .workflow-copy .section-kicker { color: #6ed8c9; }
        .workflow-copy h2 { color: #fff; }
        .workflow-copy p { margin: 0; color: #b8cbd0; font-size: 14px; line-height: 2; }
        .workflow-list { display: grid; grid-template-columns: 1fr 1fr; gap: 13px; }
        .workflow-step { display: flex; align-items: flex-start; gap: 12px; padding: 16px 14px; border: 1px solid rgba(255,255,255,.1); border-radius: 12px; background: rgba(255,255,255,.045); }
        .step-number { width: 26px; height: 26px; flex: 0 0 26px; display: grid; place-items: center; border-radius: 8px; color: #87e0d3; background: rgba(24,182,164,.15); font-size: 11px; font-weight: 800; }
        .workflow-step strong { display: block; font-size: 12px; }
        .workflow-step small { display: block; margin-top: 5px; color: #9eb4ba; font-size: 10px; line-height: 1.6; }

        .closing { padding-block: 0 86px; }
        .closing-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 28px;
            padding: 31px 36px;
            border: 1px solid #dcebe8;
            border-radius: 16px;
            background: #fbf7ed;
        }
        .closing-card h2 { margin: 0 0 7px; font-size: 22px; }
        .closing-card p { margin: 0; color: var(--muted); font-size: 13px; }
        .closing-card .button { flex: 0 0 auto; color: #161006; background: var(--gold); }
        .closing-card .button:hover { background: #e8b64e; }
        footer { color: #b4c5ca; background: var(--navy); }
        .footer-inner { min-height: 82px; display: flex; align-items: center; justify-content: space-between; gap: 20px; font-size: 11px; }
        .footer-links { display: flex; gap: 20px; }
        .footer-links a:hover { color: #fff; }

        .expanded-features { background: var(--paper); }
        .expanded-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .expanded-card {
            position: relative;
            overflow: hidden;
            min-height: 205px;
            padding: 24px;
            border: 1px solid #e3ece9;
            border-radius: 15px;
            background: #fff;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .expanded-card:hover { transform: translateY(-4px); box-shadow: 0 16px 35px rgba(18,55,67,.08); }
        .expanded-card::after {
            position: absolute;
            inset-inline-end: -30px;
            bottom: -48px;
            width: 110px;
            height: 110px;
            border: 1px solid #edf4f2;
            border-radius: 50%;
            box-shadow: 0 0 0 15px #f8fbfa;
            content: "";
        }
        .expanded-card-top { display: flex; align-items: center; justify-content: space-between; }
        .expanded-card-number { color: #a4b8b8; font-size: 11px; font-weight: 800; letter-spacing: 1px; }
        .expanded-card h3 { margin: 20px 0 8px; color: #183b4a; font-size: 16px; }
        .expanded-card p { max-width: 290px; margin: 0; color: #70838b; font-size: 12px; line-height: 1.9; }
        .tag-list { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 15px; }
        .tag { padding: 5px 8px; border-radius: 6px; color: #4c6a73; background: #f0f6f4; font-size: 9px; font-weight: 700; }

        .workspace-section {
            padding-block: 68px 74px;
            background: linear-gradient(180deg, #f7faf9, #fff);
        }
        .workspace-section .section-heading { margin-bottom: 30px; }
        .workspace-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .workspace-card {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 270px;
            padding: 27px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: #fff;
        }
        .workspace-card::before {
            position: absolute;
            inset: 0 0 auto;
            height: 4px;
            background: linear-gradient(90deg, #0b897e, #62d2c2);
            content: "";
        }
        .workspace-card:hover { transform: translateY(-5px); box-shadow: 0 22px 45px rgba(18,55,67,.12); }
        .workspace-head { display: flex; align-items: center; gap: 13px; }
        .workspace-icon { width: 43px; height: 43px; display: grid; place-items: center; border-radius: 12px; color: #0e9388; background: #e8f7f4; }
        .workspace-icon svg { width: 21px; height: 21px; }
        .workspace-head small { display: block; margin-bottom: 4px; color: #83949a; font-size: 10px; }
        .workspace-head strong { color: #173847; font-size: 15px; }
        .workspace-card p { margin: 17px 0; color: #6f838b; font-size: 12px; line-height: 1.9; }
        .workspace-link {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            margin-top: auto;
            border-radius: 11px;
            color: #fff;
            background: #0b897e;
            font-size: 12px;
            font-weight: 800;
            transition: transform .2s ease, background .2s ease, box-shadow .2s ease;
        }
        .workspace-link:hover { transform: translateY(-2px); background: #08766d; box-shadow: 0 8px 18px rgba(11,137,126,.2); }
        .workspace-link span { transition: transform .2s ease; }
        .workspace-link:hover span { transform: translateX(-4px); }
        .workspace-footnote { margin: 22px 0 0; color: #788b91; font-size: 11px; text-align: center; }

        .integration { padding-block: 10px 92px; }
        .integration-panel {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr .9fr;
            align-items: center;
            gap: 54px;
            padding: 54px 58px;
            border-radius: 20px;
            color: #fff;
            background:
                radial-gradient(ellipse at 12% 100%, rgba(45,212,191,.14), transparent 40%),
                linear-gradient(125deg, var(--navy-light), var(--navy));
        }
        .integration-copy, .api-card { position: relative; z-index: 1; }
        .integration-copy .section-kicker { color: #71d6c8; }
        .integration-copy h2 { color: #fff; }
        .integration-copy > p { max-width: 470px; margin: 0; color: #bfd0d3; font-size: 13px; line-height: 2; }
        .integration-points { display: grid; gap: 11px; margin: 21px 0 24px; }
        .integration-point { display: flex; align-items: center; gap: 9px; color: #d3e1e2; font-size: 11px; }
        .integration-point span { width: 19px; height: 19px; display: grid; place-items: center; border-radius: 50%; color: #79dfd0; background: rgba(24,182,164,.17); font-size: 10px; }
        .integration-copy .button { min-height: 43px; padding-inline: 15px; color: #072923; background: #62d2c2; font-size: 12px; }
        .api-card { overflow: hidden; border: 1px solid rgba(255,255,255,.13); border-radius: 14px; background: rgba(4,22,32,.62); box-shadow: 0 22px 55px rgba(0,0,0,.2); }
        .api-card-head { display: flex; align-items: center; justify-content: space-between; padding: 14px 17px; border-bottom: 1px solid rgba(255,255,255,.1); color: #dce8e7; font-size: 10px; }
        .api-dots { display: flex; gap: 5px; }
        .api-dots i { width: 7px; height: 7px; border-radius: 50%; background: #f1b85e; }
        .api-dots i:nth-child(2) { background: #66c8ad; }
        .api-dots i:nth-child(3) { background: #6a9bc0; }
        .api-content { padding: 18px; }
        .api-label { margin-bottom: 12px; color: #83a0a5; font-size: 9px; }
        .api-line { display: flex; align-items: center; gap: 9px; margin: 9px 0; color: #d5e5e3; font-family: Consolas, monospace; font-size: 10px; direction: ltr; text-align: left; }
        .api-method { min-width: 39px; padding: 5px 6px; border-radius: 5px; color: #74dfce; background: rgba(24,182,164,.13); font-size: 8px; font-weight: 800; text-align: center; }
        .api-method.post { color: #f3c677; background: rgba(242,185,95,.12); }
        .api-divider { height: 1px; margin: 14px 0; background: rgba(255,255,255,.1); }
        .api-status { display: flex; align-items: center; gap: 7px; color: #a9c2c2; font-size: 9px; }
        .api-status::before { width: 6px; height: 6px; border-radius: 50%; background: #56d2ad; content: ""; }
        .api-caption { margin-top: 15px; color: #809ca2; font-size: 9px; line-height: 1.8; }

        .faq-section { padding-block: 84px 94px; background: #f7faf9; }
        .faq-layout { display: grid; grid-template-columns: .72fr 1.28fr; align-items: start; gap: 55px; }
        .faq-intro .section-kicker { display: block; margin-top: 8px; }
        .faq-intro h2 { margin-top: 12px; font-size: 32px; }
        .faq-intro p { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.9; }
        .faq-list { display: grid; gap: 10px; }
        .faq-item { overflow: hidden; border: 1px solid #e3ece9; border-radius: 11px; background: #fff; }
        .faq-item summary { position: relative; padding: 17px 18px; color: #193b4a; font-size: 12px; font-weight: 800; cursor: pointer; list-style: none; }
        .faq-item summary::-webkit-details-marker { display: none; }
        .faq-item summary::after { position: absolute; inset-inline-end: 18px; color: #0e9388; content: "+"; font-size: 18px; font-weight: 400; }
        .faq-item[open] summary::after { content: "−"; }
        .faq-item p { margin: 0; padding: 0 18px 17px; color: #6e8289; font-size: 11px; line-height: 1.9; }

        @keyframes marina-fade-up {
            from { opacity: 0; transform: translateY(28px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes marina-slide-in {
            from { opacity: 0; transform: translateX(-14px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes marina-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        @keyframes marina-pulse {
            0%, 100% { transform: scale(1); opacity: .7; }
            50% { transform: scale(1.15); opacity: 1; }
        }
        @keyframes marina-shimmer {
            from { transform: translateX(130%) rotate(18deg); }
            to { transform: translateX(-130%) rotate(18deg); }
        }

        .topbar {
            position: sticky;
            top: 0;
            border-bottom: 1px solid rgba(255,255,255,.07);
            backdrop-filter: blur(18px);
            box-shadow: 0 10px 35px rgba(5,10,18,.08);
        }
        .hero { isolation: isolate; }
        .hero::before {
            position: absolute;
            z-index: 0;
            inset: 12% auto auto 68%;
            width: 360px;
            height: 360px;
            border: 1px solid rgba(217,164,65,.12);
            border-radius: 50%;
            box-shadow: 0 0 90px rgba(45,212,191,.08), inset 0 0 70px rgba(217,164,65,.025);
            content: "";
            animation: marina-pulse 7s ease-in-out infinite;
        }
        .hero-copy { animation: marina-fade-up .8s cubic-bezier(.22,.75,.24,1) both; }
        .preview-wrap { animation: marina-fade-up 1s .18s cubic-bezier(.22,.75,.24,1) both; }
        .preview { transition: transform .18s ease-out, box-shadow .35s ease; will-change: transform; }
        .preview-glow { animation: marina-pulse 5s ease-in-out infinite; }
        .preview-caption { animation: marina-float 4.5s ease-in-out infinite; }
        .metric { animation: marina-fade-up .65s cubic-bezier(.22,.75,.24,1) both; }
        .metric:nth-child(2) { animation-delay: .12s; }
        .metric:nth-child(3) { animation-delay: .24s; }
        .trip-row { animation: marina-slide-in .55s cubic-bezier(.22,.75,.24,1) both; }
        .trip-row:nth-of-type(3) { animation-delay: .18s; }
        .trip-row:nth-of-type(4) { animation-delay: .3s; }
        .brand-mark { transition: transform .25s ease, border-color .25s ease, background .25s ease; }
        .brand:hover .brand-mark { transform: rotate(-5deg) scale(1.05); border-color: rgba(217,164,65,.55); background: rgba(217,164,65,.12); }
        .nav-links a { position: relative; padding-block: 8px; }
        .nav-links a::after {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 0;
            height: 2px;
            border-radius: 99px;
            background: var(--gold);
            content: "";
            transition: width .25s ease;
        }
        .nav-links a:hover::after { width: 100%; }
        .nav-login, .button { position: relative; overflow: hidden; }
        .nav-login::before, .button::before {
            position: absolute;
            top: -50%;
            width: 35%;
            height: 200%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.28), transparent);
            content: "";
            transform: translateX(130%) rotate(18deg);
        }
        .nav-login:hover::before, .button:hover::before { animation: marina-shimmer .75s ease; }
        .feature, .expanded-card, .workspace-card, .workflow-step, .trust-item, .faq-item {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .65s cubic-bezier(.22,.75,.24,1), transform .65s cubic-bezier(.22,.75,.24,1), box-shadow .25s ease, border-color .25s ease;
        }
        .feature.is-visible, .expanded-card.is-visible, .workspace-card.is-visible, .workflow-step.is-visible, .trust-item.is-visible, .faq-item.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        .feature:nth-child(2), .expanded-card:nth-child(2), .workspace-card:nth-child(2) { transition-delay: .08s; }
        .feature:nth-child(3), .expanded-card:nth-child(3), .workspace-card:nth-child(3) { transition-delay: .16s; }
        .feature:nth-child(4), .expanded-card:nth-child(4) { transition-delay: .24s; }
        .expanded-card:nth-child(5) { transition-delay: .32s; }
        .expanded-card:nth-child(6) { transition-delay: .40s; }
        .feature-icon, .workspace-icon, .trust-icon { transition: transform .25s ease, background .25s ease, color .25s ease; }
        .feature:hover .feature-icon, .workspace-card:hover .workspace-icon, .trust-item:hover .trust-icon {
            transform: translateY(-4px) rotate(-3deg) scale(1.04);
            color: #161006;
            background: var(--gold);
        }
        .expanded-card::before {
            position: absolute;
            z-index: 0;
            inset: 0;
            border-radius: inherit;
            background: radial-gradient(circle at 80% 20%, rgba(217,164,65,.09), transparent 28%);
            content: "";
            opacity: 0;
            transition: opacity .35s ease;
        }
        .expanded-card > * { position: relative; z-index: 1; }
        .expanded-card:hover::before { opacity: 1; }
        .workflow-panel, .integration-panel, .closing-card {
            transition: transform .35s ease, box-shadow .35s ease, border-color .35s ease;
        }
        .workflow-panel:hover, .integration-panel:hover { transform: translateY(-4px); box-shadow: 0 28px 70px rgba(13,18,32,.12); }
        .workflow-step { transition-delay: calc(var(--step-index, 0) * 70ms); }
        .faq-item summary::after { transition: transform .25s ease; }
        .faq-item[open] summary::after { transform: rotate(180deg); }

        .nav-actions { display: flex; align-items: center; gap: 9px; }
        .home-language-switcher { margin-inline-start: auto; }
        .home-language-switcher .locale-switcher select {
            border-color: rgba(255,255,255,.16);
            background: rgba(255,255,255,.07);
            color: #fff;
        }
        .home-language-switcher .locale-switcher select option { background: var(--navy); color: #fff; }
        .hero.platform-hero {
            background:
                radial-gradient(circle at 78% 35%, rgba(217,164,65,.16), transparent 20%),
                radial-gradient(circle at 18% 100%, rgba(45,212,191,.11), transparent 28%),
                linear-gradient(125deg, var(--navy) 8%, #111a2e 62%, var(--navy-light));
        }
        .workflow-highlight {
            min-height: 104px;
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 20px;
            margin-bottom: 34px;
            padding: 22px 26px;
            border: 1px solid #eadfbe;
            border-radius: 18px;
            background: linear-gradient(110deg, #fff 30%, #fbf6e9);
            box-shadow: 0 16px 40px rgba(31,41,55,.07);
        }
        .workflow-highlight strong { display: block; color: #1f2937; font-size: 18px; }
        .workflow-highlight small { display: block; margin-top: 6px; color: var(--muted); }
        .workflow-highlight .button { color: #161006; background: var(--gold); }

        .topbar { background: #fff; color: var(--ink); border-bottom-color: var(--line); }
        .topbar .brand-name { color: #173847; }
        .nav-links { color: #526875; }
        .nav-links a:hover { color: var(--teal-dark); }
        .nav-login { border-color: #d5e0e8; color: #173847; }
        .nav-login:hover { border-color: var(--teal-dark); background: #e8f7f4; }
        .brand-mark { border-color: #dce8e9; background: #e8f7f4; }
        .home-language-switcher .locale-switcher select {
            border-color: #dce3ec;
            background: #fff;
            color: #344054;
        }
        .home-language-switcher .locale-switcher select option { background: #fff; color: #344054; }
        .theme-toggle {
            border-color: #dce3ec;
            background: #fff;
            color: #526875;
        }
        .theme-toggle:hover { border-color: var(--gold); background: #fbf6e9; }
        .hero, .hero.platform-hero {
            color: var(--ink);
            background:
                radial-gradient(circle at 78% 35%, rgba(217,164,65,.14), transparent 20%),
                radial-gradient(circle at 18% 100%, rgba(45,212,191,.12), transparent 28%),
                linear-gradient(125deg, #f8fbfc 8%, #eff7f7 62%, #e8f3f4);
        }
        .hero::after { border-color: rgba(15,76,83,.1); box-shadow: 0 0 0 56px rgba(15,76,83,.025), 0 0 0 112px rgba(15,76,83,.018); }
        .hero::before { border-color: rgba(15,76,83,.12); box-shadow: 0 0 90px rgba(45,212,191,.12), inset 0 0 70px rgba(217,164,65,.05); }
        .eyebrow { color: #087f78; }
        h1 { color: #173847; }
        .hero-copy > p { color: #526875; }
        .button-outline { border-color: #b7cbd0; color: #173847; }
        .button-outline:hover { border-color: #6b9295; background: rgba(255,255,255,.7); }
        .hero-note { color: #637b83; }
        .hero-note svg { color: #0b9487; }
        .preview-glow { background: rgba(45,212,191,.13); }
        .preview-caption { border-color: #d5e6e3; color: #173847; background: #fff; box-shadow: 0 12px 25px rgba(31,41,55,.12); }
        .preview-caption small { color: #71858b; }
        .caption-icon { color: #087f78; background: #e5f6f2; }
        .workflow-panel {
            border: 1px solid #dce8e9;
            color: #173847;
            background: linear-gradient(120deg, #f1f8f7, #fff);
        }
        .workflow-panel::before { border-color: rgba(15,76,83,.1); box-shadow: 0 0 0 45px rgba(15,76,83,.025), 0 0 0 90px rgba(15,76,83,.018); }
        .workflow-copy .section-kicker { color: #087f78; }
        .workflow-copy h2 { color: #173847; }
        .workflow-copy p { color: #526875; }
        .workflow-step { border-color: #dce8e9; background: rgba(255,255,255,.78); }
        .step-number { color: #087f78; background: #e5f6f2; }
        .workflow-step small { color: #71858b; }
        .integration-panel {
            border: 1px solid #dce8e9;
            color: #173847;
            background:
                radial-gradient(ellipse at 12% 100%, rgba(45,212,191,.12), transparent 40%),
                linear-gradient(125deg, #f1f8f7, #fff);
        }
        .integration-copy .section-kicker { color: #087f78; }
        .integration-copy h2 { color: #173847; }
        .integration-copy > p { color: #526875; }
        .integration-point { color: #405c65; }
        .integration-point span { color: #087f78; background: #e5f6f2; }
        .integration-copy .button { color: #072923; background: #62d2c2; }
        .api-card { border-color: #dce8e9; background: #fff; box-shadow: 0 22px 55px rgba(31,41,55,.1); }
        .api-card-head { border-bottom-color: #e5ecee; color: #314f59; }
        .api-label { color: #71858b; }
        .api-line { color: #314f59; }
        .api-method { color: #087f78; background: #e5f6f2; }
        .api-method.post { color: #946718; background: #fbf2df; }
        .api-divider { background: #e5ecee; }
        .api-status { color: #526875; }
        .api-caption { color: #71858b; }
        footer { color: #637b83; background: #fff; border-top: 1px solid var(--line); }
        .footer-links a:hover { color: var(--teal-dark); }

        @media (max-width: 950px) {
            .hero-grid { min-height: 0; grid-template-columns: 1fr 1fr; gap: 30px; }
            .feature-grid { grid-template-columns: repeat(2, 1fr); }
            .workflow-panel { gap: 30px; padding: 44px 38px; }
            .expanded-grid { grid-template-columns: repeat(2, 1fr); }
            .workspace-grid { grid-template-columns: 1fr 1fr; }
            .integration-panel { gap: 32px; padding: 42px 36px; }
            .faq-layout { gap: 32px; }
            .trust-inner { flex-wrap: wrap; justify-content: center; padding-block: 22px; }
            .trust-lead { max-width: none; width: 100%; text-align: center; }
        }
        @media (max-width: 680px) {
            .shell { width: min(100% - 34px, 520px); }
            .nav { min-height: 70px; }
            .nav-links { display: none; }
            .nav-login { padding: 9px 13px; font-size: 12px; }
            .brand-mark { width: 38px; height: 38px; }
            .brand-name { font-size: 17px; }
            .hero-grid { grid-template-columns: 1fr; gap: 30px; padding-block: 57px 58px; }
            h1 { margin-top: 17px; font-size: clamp(37px, 10vw, 49px); }
            .hero-copy > p { font-size: 15px; }
            .preview-wrap { width: 100%; padding: 6px 0 22px; }
            .preview { transform: none; }
            .workflow-highlight { grid-template-columns: 1fr; padding: 20px; }
            .workflow-highlight .button { width: 100%; }
            .preview-body { min-height: 300px; grid-template-columns: 1fr 43px; }
            .preview-content { padding: 17px 13px 14px; }
            .preview-sidebar { gap: 17px; }
            .preview-caption { inset-inline-end: 8px; bottom: 1px; }
            .trust-inner { gap: 17px 22px; }
            .trust-item { font-size: 11px; }
            .trust-icon { width: 32px; height: 32px; }
            .section { padding-block: 72px; }
            .section-heading { margin-bottom: 30px; }
            .feature-grid { grid-template-columns: 1fr; gap: 12px; }
            .feature { min-height: 0; padding: 21px; }
            .feature h3 { margin-top: 14px; }
            .workflow { padding-bottom: 70px; }
            .workflow-panel { grid-template-columns: 1fr; gap: 25px; padding: 31px 22px; }
            .workflow-list { grid-template-columns: 1fr; }
            .expanded-grid, .workspace-grid { grid-template-columns: 1fr; }
            .expanded-card { min-height: 0; padding: 21px; }
            .workspace-section { padding-block: 66px 72px; }
            .integration { padding-bottom: 70px; }
            .integration-panel { grid-template-columns: 1fr; gap: 27px; padding: 31px 22px; }
            .faq-section { padding-block: 66px 72px; }
            .faq-layout { grid-template-columns: 1fr; gap: 24px; }
            .faq-intro h2 { font-size: 28px; }
            .closing { padding-bottom: 62px; }
            .closing-card { align-items: flex-start; flex-direction: column; padding: 25px 22px; }
            .closing-card .button { width: 100%; }
            .footer-inner { align-items: flex-start; flex-direction: column; justify-content: center; padding-block: 20px; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
            .feature, .expanded-card, .workspace-card, .workflow-step, .trust-item, .faq-item {
                opacity: 1;
                transform: none;
            }
        }

        .theme-toggle {
            width: 42px;
            height: 42px;
            display: grid;
            flex: 0 0 auto;
            place-items: center;
            border: 1px solid rgba(255,255,255,.14);
            border-radius: 12px;
            background: rgba(255,255,255,.055);
            color: #fff;
            cursor: pointer;
            transition: transform .25s ease, border-color .25s ease, background .25s ease;
        }
        .theme-toggle:hover { transform: translateY(-2px); border-color: var(--gold); background: rgba(217,164,65,.12); }
        .theme-toggle svg { width: 19px; height: 19px; transition: transform .45s cubic-bezier(.22,.75,.24,1); }
        .theme-toggle:hover svg { transform: rotate(22deg); }
        .theme-toggle .icon-moon { display: none; }
        .theme-toggle .icon-sun { display: block; }
        html:not([data-theme="dark"]) .theme-toggle .icon-sun { color: #111827; }
        html[data-theme="dark"] .theme-toggle .icon-sun { display: none; }
        html[data-theme="dark"] .theme-toggle .icon-moon { display: block; }
        body,
        .trust-strip,
        .feature,
        .expanded-features,
        .expanded-card,
        .workspace-card,
        .faq-section,
        .faq-item,
        .closing-card,
        .workflow-highlight,
        h2 {
            transition: color .35s ease, background-color .35s ease, border-color .35s ease, box-shadow .35s ease;
        }

        html[data-theme="dark"] {
            color-scheme: dark;
            --ink: #e5edf2;
            --muted: #9aabba;
            --navy: #080f1c;
            --navy-light: #111d31;
            --teal: #49d6c3;
            --teal-dark: #51caba;
            --gold: #e7bd67;
            --paper: #101a2a;
            --line: #26364a;
        }
        html[data-theme="dark"] body { color: var(--ink); background: #0b1220; }
        html[data-theme="dark"] .topbar { background: #080f1c; color: #fff; border-bottom-color: rgba(255,255,255,.07); }
        html[data-theme="dark"] .topbar .brand-name { color: #fff; }
        html[data-theme="dark"] .nav-links { color: #c3d1d6; }
        html[data-theme="dark"] .nav-links a:hover { color: #fff; }
        html[data-theme="dark"] .nav-login { border-color: rgba(255,255,255,.22); color: #fff; }
        html[data-theme="dark"] .nav-login:hover { border-color: var(--teal); background: rgba(24,182,164,.12); }
        html[data-theme="dark"] .brand-mark { border-color: rgba(255,255,255,.2); background: rgba(24,182,164,.12); }
        html[data-theme="dark"] .home-language-switcher .locale-switcher select { border-color: rgba(255,255,255,.16); background: rgba(255,255,255,.07); color: #fff; }
        html[data-theme="dark"] .home-language-switcher .locale-switcher select option { background: var(--navy); color: #fff; }
        html[data-theme="dark"] .theme-toggle { border-color: rgba(255,255,255,.14); background: rgba(255,255,255,.055); color: #fff; }
        html[data-theme="dark"] .theme-toggle:hover { border-color: var(--gold); background: rgba(217,164,65,.12); }
        html[data-theme="dark"] .hero,
        html[data-theme="dark"] .hero.platform-hero {
            color: #fff;
            background:
                radial-gradient(circle at 78% 35%, rgba(231,189,103,.12), transparent 20%),
                radial-gradient(circle at 18% 100%, rgba(73,214,195,.09), transparent 28%),
                linear-gradient(125deg, var(--navy) 8%, #0d1728 62%, var(--navy-light));
        }
        html[data-theme="dark"] .hero::after { border-color: rgba(255,255,255,.06); box-shadow: 0 0 0 56px rgba(255,255,255,.018), 0 0 0 112px rgba(255,255,255,.014); }
        html[data-theme="dark"] .hero::before { border-color: rgba(231,189,103,.12); box-shadow: 0 0 90px rgba(73,214,195,.08), inset 0 0 70px rgba(231,189,103,.025); }
        html[data-theme="dark"] .eyebrow { color: #8de0d4; }
        html[data-theme="dark"] h1 { color: #fff; }
        html[data-theme="dark"] .hero-copy > p { color: #c2d0d5; }
        html[data-theme="dark"] .button-outline { border-color: rgba(255,255,255,.24); color: #fff; }
        html[data-theme="dark"] .button-outline:hover { border-color: rgba(255,255,255,.6); background: rgba(255,255,255,.06); }
        html[data-theme="dark"] .hero-note { color: #93aab3; }
        html[data-theme="dark"] .hero-note svg { color: #58cdbb; }
        html[data-theme="dark"] .preview { border-color: #29394b; background: #111d2e; box-shadow: 0 30px 75px rgba(0,0,0,.4); }
        html[data-theme="dark"] .preview-head { border-bottom-color: #29394b; color: #dce8ee; }
        html[data-theme="dark"] .preview-brand { color: #dce8ee; }
        html[data-theme="dark"] .preview-dot { box-shadow: 0 0 0 4px #1a3836; }
        html[data-theme="dark"] .preview-head span:last-child { color: #9aabba; }
        html[data-theme="dark"] .preview-caption { border-color: rgba(255,255,255,.24); color: #fff; background: #123c49; box-shadow: 0 12px 25px rgba(0,0,0,.2); }
        html[data-theme="dark"] .preview-caption small { color: #b6c8cc; }
        html[data-theme="dark"] .caption-icon { color: #8ee4d7; background: rgba(24,182,164,.16); }
        html[data-theme="dark"] .workflow-panel {
            border-color: transparent;
            color: #fff;
            background: linear-gradient(120deg, var(--navy-light), var(--navy));
        }
        html[data-theme="dark"] .workflow-panel::before { border-color: rgba(255,255,255,.08); box-shadow: 0 0 0 45px rgba(255,255,255,.025), 0 0 0 90px rgba(255,255,255,.018); }
        html[data-theme="dark"] .workflow-copy .section-kicker { color: #6ed8c9; }
        html[data-theme="dark"] .workflow-copy h2 { color: #fff; }
        html[data-theme="dark"] .workflow-copy p { color: #b8cbd0; }
        html[data-theme="dark"] .workflow-step { border-color: rgba(255,255,255,.1); background: rgba(255,255,255,.045); }
        html[data-theme="dark"] .step-number { color: #87e0d3; background: rgba(24,182,164,.15); }
        html[data-theme="dark"] .workflow-step small { color: #9eb4ba; }
        html[data-theme="dark"] .integration-panel {
            border-color: transparent;
            color: #fff;
            background:
                radial-gradient(ellipse at 12% 100%, rgba(45,212,191,.14), transparent 40%),
                linear-gradient(125deg, var(--navy-light), var(--navy));
        }
        html[data-theme="dark"] .integration-copy .section-kicker { color: #71d6c8; }
        html[data-theme="dark"] .integration-copy h2 { color: #fff; }
        html[data-theme="dark"] .integration-copy > p { color: #bfd0d3; }
        html[data-theme="dark"] .integration-point { color: #d3e1e2; }
        html[data-theme="dark"] .integration-point span { color: #79dfd0; background: rgba(24,182,164,.17); }
        html[data-theme="dark"] .api-card { border-color: rgba(255,255,255,.13); background: rgba(4,22,32,.82); box-shadow: 0 22px 55px rgba(0,0,0,.2); }
        html[data-theme="dark"] .api-card-head { border-bottom-color: rgba(255,255,255,.1); color: #dce8e7; }
        html[data-theme="dark"] .api-label { color: #83a0a5; }
        html[data-theme="dark"] .api-line { color: #d5e5e3; }
        html[data-theme="dark"] .api-method { color: #74dfce; background: rgba(24,182,164,.13); }
        html[data-theme="dark"] .api-method.post { color: #f3c677; background: rgba(242,185,95,.12); }
        html[data-theme="dark"] .api-divider { background: rgba(255,255,255,.1); }
        html[data-theme="dark"] .api-status { color: #a9c2c2; }
        html[data-theme="dark"] .api-caption { color: #809ca2; }
        html[data-theme="dark"] footer { color: #b4c5ca; background: var(--navy); border-top-color: transparent; }
        html[data-theme="dark"] .footer-links a:hover { color: #fff; }
        html[data-theme="dark"] .trust-strip { background: #0e1828; }
        html[data-theme="dark"] .trust-lead { color: #afc0cc; }
        html[data-theme="dark"] .trust-item { color: #d3e1e9; }
        html[data-theme="dark"] .trust-icon,
        html[data-theme="dark"] .feature-icon,
        html[data-theme="dark"] .workspace-icon { color: #66d8c8; background: #16352f; }
        html[data-theme="dark"] h2 { color: #e6eef4; }
        html[data-theme="dark"] .feature,
        html[data-theme="dark"] .expanded-card,
        html[data-theme="dark"] .workspace-card,
        html[data-theme="dark"] .faq-item {
            border-color: #26384b;
            background: #111d2e;
        }
        html[data-theme="dark"] .feature:hover,
        html[data-theme="dark"] .expanded-card:hover,
        html[data-theme="dark"] .workspace-card:hover { border-color: #3a6e6a; }
        html[data-theme="dark"] .feature h3,
        html[data-theme="dark"] .expanded-card h3,
        html[data-theme="dark"] .workspace-head strong,
        html[data-theme="dark"] .faq-item summary { color: #dce8ee; }
        html[data-theme="dark"] .feature p,
        html[data-theme="dark"] .expanded-card p,
        html[data-theme="dark"] .workspace-card p,
        html[data-theme="dark"] .workspace-head small,
        html[data-theme="dark"] .workspace-footnote,
        html[data-theme="dark"] .faq-item p { color: #9aabba; }
        html[data-theme="dark"] .expanded-card { border-color: #26384b; }
        html[data-theme="dark"] .expanded-card::after {
            border-color: #253749;
            box-shadow: 0 0 0 15px #152235;
        }
        html[data-theme="dark"] .tag { color: #b6c8d2; background: #1b2b3d; }
        html[data-theme="dark"] .workspace-section { background: linear-gradient(180deg, #0d1726, #0b1220); }
        html[data-theme="dark"] .workspace-card::before { background: linear-gradient(90deg, #49d6c3, #227c78); }
        html[data-theme="dark"] .workspace-card:hover { box-shadow: 0 22px 45px rgba(0,0,0,.3); }
        html[data-theme="dark"] .workspace-link { color: #071b19; background: #49c9b8; }
        html[data-theme="dark"] .workspace-link:hover { background: #60d9c8; box-shadow: 0 8px 18px rgba(73,214,195,.14); }
        html[data-theme="dark"] .faq-section { background: #0d1726; }
        html[data-theme="dark"] .faq-item { border-color: #29394b; }
        html[data-theme="dark"] .faq-item summary::after { color: #66d8c8; }
        html[data-theme="dark"] .closing-card {
            border-color: #374337;
            background: linear-gradient(110deg, #172333 30%, #26251f);
        }
        html[data-theme="dark"] .workflow-highlight {
            border-color: #4b422f;
            background: linear-gradient(110deg, #111d2e 30%, #25251f);
        }
        html[data-theme="dark"] .workflow-highlight strong { color: #e6eef4; }
        html[data-theme="dark"] .workflow-highlight .button { color: #201807; }
        html[data-theme="dark"] .theme-toggle { border-color: rgba(231,189,103,.42); background: rgba(231,189,103,.12); }
        @media (max-width: 680px) {
            .nav { gap: 8px; }
            .brand { gap: 8px; }
            .brand-mark { width: 34px; height: 34px; }
            .brand-name { font-size: 15px; }
            .home-language-switcher .locale-switcher select { max-width: 82px; padding-inline: 5px; font-size: 10px; }
            .nav-actions { gap: 4px; }
            .theme-toggle { width: 36px; height: 36px; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="shell nav">
            <a class="brand" href="{{ url('/') }}" aria-label="MarinaX - الصفحة الرئيسية">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M3 16.5c2.4 0 2.4-1.6 4.8-1.6s2.4 1.6 4.8 1.6 2.4-1.6 4.8-1.6 2.4 1.6 4.8 1.6M5.2 12.3l2.4-5.8h8.8l2.4 5.8M9 6.5V4.8h6v1.7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="brand-name">Marina<span>X</span></span>
            </a>
            <nav class="nav-links" aria-label="التنقل الرئيسي">
                <a href="#features">المميزات</a>
                <a href="#capabilities">الإمكانيات</a>
                <a href="#workspaces">لوحات التحكم</a>
                <a href="#integrations">التكامل</a>
            </nav>
            <div class="home-language-switcher">
                @include('shared.language-switcher')
            </div>
            <div class="nav-actions">
                <button class="theme-toggle" type="button" data-theme-toggle aria-label="تفعيل الوضع الداكن" title="تفعيل الوضع الداكن" aria-pressed="false">
                    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.7"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20.2 15.1A8.5 8.5 0 0 1 8.9 3.8 8.5 8.5 0 1 0 20.2 15.1Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main>
        <section class="hero platform-hero">
            <div class="shell hero-grid">
                <div class="hero-copy">
                    <span class="eyebrow">منصة MarinaX لإدارة الأعمال السياحية</span>
                    <h1>كل عمليات شركتك،<br><span>في مكان واحد</span></h1>
                    <p>أدر الرحلات والحجوزات والموارد والفريق من منصة واحدة، وتابع سير العمل بصلاحيات واضحة ورؤية أدق.</p>
                    <div class="hero-actions">
                        <a class="button button-primary" href="#workspaces">
                            استكشف لوحات التحكم
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 12H5m0 0 6 6m-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                        <a class="button button-outline" href="#capabilities">تعرّف على المنصة</a>
                    </div>
                    <div class="hero-note">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 5 6v5c0 4.5 2.9 8.2 7 10 4.1-1.8 7-5.5 7-10V6l-7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        صلاحيات منظمة · بيانات واضحة · تشغيل مترابط
                    </div>
                </div>

                <div class="preview-wrap" aria-label="معاينة لوحة تشغيل MarinaX">
                    <div class="preview-glow"></div>
                    <div class="preview">
                        <div class="preview-head">
                            <div class="preview-brand"><span class="preview-dot"></span> MarinaX <span style="color:#82939a;font-weight:500">/ لوحة التشغيل</span></div>
                            <span>معاينة توضيحية</span>
                        </div>
                        <div class="preview-body">
                            <div class="preview-content">
                                <p class="preview-title">ملخص التشغيل</p>
                                <p class="preview-subtitle">نظرة عامة على نشاط الشركة</p>
                                <div class="metric-grid">
                                    <div class="metric"><span class="metric-label">الحجوزات اليوم</span><strong>24</strong></div>
                                    <div class="metric"><span class="metric-label">رحلات نشطة</span><strong>08</strong></div>
                                    <div class="metric"><span class="metric-label">مهام مفتوحة</span><strong class="teal">12</strong></div>
                                </div>
                                <div class="preview-section-head"><span>الرحلات القادمة</span><span>عرض الكل</span></div>
                                <div class="trip-row"><span><strong class="trip-name">برنامج سياحي</strong><small class="trip-meta">غدًا · 09:30 صباحًا</small></span><span>18 مسافرًا</span><span class="trip-status">جاهز</span></div>
                                <div class="trip-row"><span><strong class="trip-name">رحلة بحرية</strong><small class="trip-meta">الخميس · 11:00 صباحًا</small></span><span>12 مسافرًا</span><span class="trip-status">مجدول</span></div>
                            </div>
                            <div class="preview-sidebar" aria-hidden="true">
                                <span class="side-icon active">⌂</span><span class="side-icon">⌖</span><span class="side-icon">▤</span><span class="side-icon">◎</span><span class="side-icon">▥</span>
                            </div>
                        </div>
                    </div>
                    <div class="preview-caption">
                        <span class="caption-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 18V6m0 12h16M8 14l3-4 3 2 5-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span><strong>رؤية أوضح لفريقك</strong><small>العمليات والمهام في مساحة واحدة</small></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="trust-strip" aria-label="مجالات المنصة">
            <div class="shell trust-inner">
                <div class="trust-lead">أدوات تشغيل مترابطة تدعم فريقك في كل خطوة</div>
                <div class="trust-item"><span class="trust-icon" aria-hidden="true">⌖</span>إدارة الرحلات</div>
                <div class="trust-item"><span class="trust-icon" aria-hidden="true">▤</span>متابعة الحجوزات</div>
                <div class="trust-item"><span class="trust-icon" aria-hidden="true">◎</span>صلاحيات واضحة</div>
            </div>
        </section>

        <section class="workspace-section" id="workspaces">
            <div class="shell">
                <div class="section-heading">
                    <span class="section-kicker">وصول سريع</span>
                    <h2>لوحات التحكم، في مكان واحد</h2>
                    <p>اختَر مساحة عملك، وسنوصلك للوحة المناسبة أو لصفحة الدخول إذا لم تكن مسجّلًا.</p>
                </div>
                <div class="workspace-grid">
                    <article class="workspace-card">
                        <div class="workspace-head">
                            <span class="workspace-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 20V9l8-5 8 5v11h-6v-6h-4v6H4Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <span><small>إدارة الشركة</small><strong>لوحة تحكم الإدارة</strong></span>
                        </div>
                        <p>الرحلات والموارد والمستخدمون والتقارير، كلها في مساحة إدارة شركتك.</p>
                        <a class="workspace-link" href="{{ route('admin.dashboard') }}" target="_blank" rel="noopener noreferrer">فتح لوحة الإدارة <span aria-hidden="true">←</span></a>
                    </article>
                    <article class="workspace-card">
                        <div class="workspace-head">
                            <span class="workspace-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20m17 0v-1.5a4 4 0 0 0-3-3.87M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-7.5a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
                            <span><small>فريق التشغيل</small><strong>لوحة تحكم الموظفين</strong></span>
                        </div>
                        <p>تابع العمليات والرحلات المسندة ومهام الفريق من مساحة العمل اليومية.</p>
                        <a class="workspace-link" href="{{ route('dashboard') }}" target="_blank" rel="noopener noreferrer">فتح لوحة الموظفين <span aria-hidden="true">←</span></a>
                    </article>
                </div>
                <p class="workspace-footnote">تظهر البيانات والإجراءات المتاحة حسب دور حسابك والصلاحيات الممنوحة له.</p>
            </div>
        </section>

        <section class="section" id="features">
            <div class="shell">
                <div class="section-heading">
                    <span class="section-kicker">أدوات العمل اليومية</span>
                    <h2>كل ما يحتاجه فريقك للتشغيل</h2>
                    <p>نظّم العمليات الأساسية لشركتك السياحية، من إعداد البرامج حتى متابعة الأداء.</p>
                </div>
                <div class="feature-grid">
                    <article class="feature">
                        <span class="feature-icon" aria-hidden="true">⌖</span>
                        <h3>برامج ورحلات</h3>
                        <p>أنشئ البرامج السياحية ونظّم تفاصيلها ومواعيدها ومواردها في مساحة واحدة.</p>
                    </article>
                    <article class="feature">
                        <span class="feature-icon" aria-hidden="true">▤</span>
                        <h3>الحجوزات والعملاء</h3>
                        <p>تابع الطلبات وتفاصيل العملاء وحالة كل حجز من البداية حتى اكتمال الخدمة.</p>
                    </article>
                    <article class="feature">
                        <span class="feature-icon" aria-hidden="true">◎</span>
                        <h3>الفريق والصلاحيات</h3>
                        <p>وزّع المهام ونظّم الوصول حسب دور كل مستخدم ومساحة عمله.</p>
                    </article>
                    <article class="feature">
                        <span class="feature-icon" aria-hidden="true">▥</span>
                        <h3>التقارير والماليات</h3>
                        <p>تابع المؤشرات المالية وتقارير التشغيل لتبني قراراتك على بيانات واضحة.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="workflow" id="workflow">
            <div class="shell">
                <div class="workflow-highlight">
                    <div>
                        <strong>منصة واحدة لكل عمليات شركتك</strong>
                        <small>اربط الرحلات والحجوزات والفريق والموارد والماليات في مسار عمل موحّد.</small>
                    </div>
                    <a class="button" href="#capabilities">اكتشف إمكانيات المنصة</a>
                </div>
            </div>
        </section>

        <section class="section expanded-features" id="capabilities">
            <div class="shell">
                <div class="section-heading">
                    <span class="section-kicker">منظومة مترابطة</span>
                    <h2>تفاصيل أكثر، من أول برنامج إلى آخر تقرير</h2>
                    <p>بدل التنقل بين قوائم متفرقة، اجمع بيانات الرحلات والعملاء والموارد والماليات في مسار واضح لفريقك.</p>
                </div>
                <div class="expanded-grid">
                    <article class="expanded-card">
                        <div class="expanded-card-top"><span class="feature-icon" aria-hidden="true">⌖</span><span class="expanded-card-number">01 / TRIPS</span></div>
                        <h3>برامج سياحية غنية بالتفاصيل</h3>
                        <p>نظّم وصف الرحلة ومحتواها وخياراتها، واربطها بالمنطقة والفنادق المناسبة.</p>
                        <div class="tag-list"><span class="tag">البرامج</span><span class="tag">المناطق</span><span class="tag">الفنادق</span></div>
                    </article>
                    <article class="expanded-card">
                        <div class="expanded-card-top"><span class="feature-icon" aria-hidden="true">◇</span><span class="expanded-card-number">02 / PRICING</span></div>
                        <h3>أسعار وشركات وعملات</h3>
                        <p>تابع الشركات والأسعار الخاصة بها والعملات المرتبطة بالتشغيل.</p>
                        <div class="tag-list"><span class="tag">الشركات</span><span class="tag">أسعار الشركات</span><span class="tag">العملات</span></div>
                    </article>
                    <article class="expanded-card">
                        <div class="expanded-card-top"><span class="feature-icon" aria-hidden="true">↗</span><span class="expanded-card-number">03 / OPERATIONS</span></div>
                        <h3>تشغيل يومي أكثر تنظيمًا</h3>
                        <p>اربط الحجز بتفاصيل الرحلة والتذكرة وتوزيع المسؤوليات بين أعضاء الفريق.</p>
                        <div class="tag-list"><span class="tag">الحجوزات</span><span class="tag">التذاكر</span><span class="tag">التوزيع</span></div>
                    </article>
                    <article class="expanded-card">
                        <div class="expanded-card-top"><span class="feature-icon" aria-hidden="true">⌁</span><span class="expanded-card-number">04 / RESOURCES</span></div>
                        <h3>موارد أسطولك ومورديك</h3>
                        <p>إدارة بيانات القوارب والمركبات والسائقين والموردين والإضافات التشغيلية.</p>
                        <div class="tag-list"><span class="tag">قوارب</span><span class="tag">مركبات</span><span class="tag">سائقون</span><span class="tag">موردون</span></div>
                    </article>
                    <article class="expanded-card">
                        <div class="expanded-card-top"><span class="feature-icon" aria-hidden="true">▥</span><span class="expanded-card-number">05 / FINANCE</span></div>
                        <h3>رؤية مالية قابلة للمتابعة</h3>
                        <p>انتقل من دليل الحسابات والقيود إلى الأرصدة والقوائم والتقارير المالية.</p>
                        <div class="tag-list"><span class="tag">القيود</span><span class="tag">دفتر الأستاذ</span><span class="tag">التقارير</span></div>
                    </article>
                    <article class="expanded-card">
                        <div class="expanded-card-top"><span class="feature-icon" aria-hidden="true">◎</span><span class="expanded-card-number">06 / CONTROL</span></div>
                        <h3>إدارة المستخدمين والصلاحيات</h3>
                        <p>نظّم أدوار الحسابات وصلاحيات الوصول بما يناسب مساحة كل مستخدم.</p>
                        <div class="tag-list"><span class="tag">الإدارة</span><span class="tag">الموظفون</span><span class="tag">الصلاحيات</span></div>
                    </article>
                </div>
            </div>
        </section>

        <section class="integration" id="integrations">
            <div class="shell">
                <div class="integration-panel">
                    <div class="integration-copy">
                        <span class="section-kicker">واجهة موقعك، متصلة بعملياتك</span>
                        <h2>اربط موقع شركتك بمنصة التشغيل</h2>
                        <p>توفر MarinaX واجهة API لمواقع الشركات لعرض البرامج والشركات والعملات، وإنشاء الحجوزات ومتابعة حالتها بصلاحيات مخصصة لكل تكامل.</p>
                        <div class="integration-points">
                            <div class="integration-point"><span aria-hidden="true">✓</span>بيانات رحلات عامة مهيّأة لموقع الشركة</div>
                            <div class="integration-point"><span aria-hidden="true">✓</span>مفاتيح API بنطاقات وصلاحيات محددة</div>
                            <div class="integration-point"><span aria-hidden="true">✓</span>حجز جديد يبدأ بحالة «قيد الانتظار»</div>
                        </div>
                        <a class="button" href="{{ route('api.documentation') }}" target="_blank" rel="noopener noreferrer">استكشف توثيق واجهة API</a>
                    </div>
                    <div class="api-card" aria-label="أمثلة توضيحية لطلبات API">
                        <div class="api-card-head"><span>مسارات واجهة الموقع</span><span class="api-dots" aria-hidden="true"><i></i><i></i><i></i></span></div>
                        <div class="api-content">
                            <div class="api-label">نقاط اتصال متاحة للتكامل</div>
                            <div class="api-line"><span class="api-method">GET</span><span>/api/v1/trips</span></div>
                            <div class="api-line"><span class="api-method">GET</span><span>/api/v1/companies</span></div>
                            <div class="api-line"><span class="api-method post">POST</span><span>/api/v1/bookings</span></div>
                            <div class="api-line"><span class="api-method">GET</span><span>/api/v1/bookings/{booking}</span></div>
                            <div class="api-divider"></div>
                            <div class="api-status">مصادقة وصلاحيات مرتبطة بالشركة</div>
                            <p class="api-caption">الأسعار تُحتسب من بيانات الخادم، ومفتاح التكرار يساعد على تجنب إنشاء الحجز نفسه مرتين عند إعادة الطلب.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="faq-section" id="questions">
            <div class="shell faq-layout">
                <div class="faq-intro">
                    <span class="section-kicker">أسئلة قبل أن تبدأ</span>
                    <h2>إجابات سريعة وواضحة</h2>
                    <p>نظرة مختصرة على طريقة تنظيم المنصة والوصول إلى الوظائف الأساسية.</p>
                </div>
                <div class="faq-list">
                    <details class="faq-item">
                        <summary>هل يمكن إدارة أكثر من شركة على المنصة؟</summary>
                        <p>نعم. تدعم المنصة شركات مستقلة، مع فصل بيانات الشركة وربط مستخدميها ومواردها ضمن نطاقها.</p>
                    </details>
                    <details class="faq-item">
                        <summary>من أين أدخل إلى النظام؟</summary>
                        <p>اختر لوحة الإدارة أو الموظفين أعلاه. إذا لم يكن لديك حساب، تواصل مع مسؤول شركتك لتحديد الوصول المناسب.</p>
                    </details>
                    <details class="faq-item">
                        <summary>هل يمكن ربط موقع الشركة بالرحلات والحجوزات؟</summary>
                        <p>تتضمن المنصة API للمواقع لقراءة بيانات الرحلات والشركات والعملات وإنشاء الحجوزات ومتابعتها. راجع التوثيق لمعرفة الطلبات والصلاحيات المطلوبة.</p>
                    </details>
                    <details class="faq-item">
                        <summary>هل يمكن منع تكرار الحجز عند إعادة الطلب؟</summary>
                        <p>يساعد مفتاح التكرار على تجنب إنشاء الحجز نفسه مرتين عند إعادة الطلب.</p>
                    </details>
                    <details class="faq-item">
                        <summary>هل تظهر كل الوظائف لكل المستخدمين؟</summary>
                        <p>لا، الوظائف المتاحة تختلف بحسب مساحة المستخدم ودوره والصلاحيات المسندة إلى حسابه.</p>
                    </details>
                </div>
            </div>
        </section>

        <section class="closing" id="contact">
            <div class="shell">
                <div class="closing-card">
                    <div>
                        <h2>جاهز لإدارة يومك بطريقة أوضح؟</h2>
                        <p>ادخل إلى مساحة عملك وتابع عمليات شركتك من منصة MarinaX.</p>
                    </div>
                    <a class="button" href="{{ route('staff.login') }}" target="_blank" rel="noopener noreferrer">دخول الموظفين والإدارة</a>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="shell footer-inner">
            <a class="brand" href="{{ url('/') }}" aria-label="MarinaX - الصفحة الرئيسية">
                <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M3 16.5c2.4 0 2.4-1.6 4.8-1.6s2.4 1.6 4.8 1.6 2.4-1.6 4.8-1.6 2.4 1.6 4.8 1.6M5.2 12.3l2.4-5.8h8.8l2.4 5.8M9 6.5V4.8h6v1.7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span class="brand-name">Marina<span>X</span></span>
            </a>
            <span>منصة لإدارة الأعمال السياحية والعمليات اليومية.</span>
            <div class="footer-links">
                <a href="{{ route('Admin.login') }}" target="_blank" rel="noopener noreferrer">دخول الإدارة</a>
            </div>
        </div>
    </footer>

    <script>
        (() => {
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            const english = {
                'التنقل الرئيسي':'Main navigation','MarinaX - الصفحة الرئيسية':'MarinaX - Home','مجالات المنصة':'Platform capabilities','أمثلة توضيحية لطلبات API':'Illustrative API request examples',
                'المميزات':'Features','الإمكانيات':'Capabilities','التكامل':'Integrations',
                'منصة MarinaX لإدارة الأعمال السياحية':'MarinaX travel business management platform','كل عمليات شركتك،':'Your business operations,','في مكان واحد':'all in one place',
                'أدر الرحلات والحجوزات والموارد والفريق من منصة واحدة، وتابع سير العمل بصلاحيات واضحة ورؤية أدق.':'Manage trips, bookings, resources and teams in one platform, with clear permissions and a better view of your operations.',
                'استكشف لوحات التحكم':'Explore dashboards','تعرّف على المنصة':'Discover the platform','صلاحيات منظمة · بيانات واضحة · تشغيل مترابط':'Organized permissions · Clear data · Connected operations',
                'معاينة لوحة تشغيل MarinaX':'MarinaX operations dashboard preview','/ لوحة التشغيل':'/ Operations dashboard','معاينة توضيحية':'Illustrative preview',
                'ملخص التشغيل':'Operations overview','نظرة عامة على نشاط الشركة':'Company activity at a glance','الحجوزات اليوم':'Bookings today','رحلات نشطة':'Active trips','مهام مفتوحة':'Open tasks',
                'الرحلات القادمة':'Upcoming trips','عرض الكل':'View all','برنامج سياحي':'Tour program','غدًا · 09:30 صباحًا':'Tomorrow · 9:30 AM','18 مسافرًا':'18 travelers','جاهز':'Ready',
                'رحلة بحرية':'Boat trip','الخميس · 11:00 صباحًا':'Thursday · 11:00 AM','12 مسافرًا':'12 travelers','مجدول':'Scheduled',
                'رؤية أوضح لفريقك':'A clearer view for your team','العمليات والمهام في مساحة واحدة':'Operations and tasks in one workspace',
                'أدوات تشغيل مترابطة تدعم فريقك في كل خطوة':'Connected tools that support your team at every step','إدارة الرحلات':'Trip management','متابعة الحجوزات':'Booking tracking','صلاحيات واضحة':'Clear permissions',
                'أدوات العمل اليومية':'Everyday tools','كل ما يحتاجه فريقك للتشغيل':'Everything your team needs to operate',
                'نظّم العمليات الأساسية لشركتك السياحية، من إعداد البرامج حتى متابعة الأداء.':'Organize your travel company’s core operations, from building programs to tracking performance.',
                'برامج ورحلات':'Programs and trips','أنشئ البرامج السياحية ونظّم تفاصيلها ومواعيدها ومواردها في مساحة واحدة.':'Create travel programs and organize their details, schedules and resources in one place.',
                'الحجوزات والعملاء':'Bookings and customers','تابع الطلبات وتفاصيل العملاء وحالة كل حجز من البداية حتى اكتمال الخدمة.':'Track requests, customer details and every booking from start to service completion.',
                'الفريق والصلاحيات':'Teams and permissions','وزّع المهام ونظّم الوصول حسب دور كل مستخدم ومساحة عمله.':'Assign tasks and organize access according to each user’s role and workspace.',
                'التقارير والماليات':'Reports and finances','تابع المؤشرات المالية وتقارير التشغيل لتبني قراراتك على بيانات واضحة.':'Track financial indicators and operational reports to make decisions based on clear data.',
                'منصة واحدة لكل عمليات شركتك':'One platform for your entire operation','اربط الرحلات والحجوزات والفريق والموارد والماليات في مسار عمل موحّد.':'Connect trips, bookings, teams, resources and finances in one workflow.','اكتشف إمكانيات المنصة':'Explore platform capabilities',
                'منظومة مترابطة':'Everything works together','تفاصيل أكثر، من أول برنامج إلى آخر تقرير':'From the first itinerary to the final report','بدل التنقل بين قوائم متفرقة، اجمع بيانات الرحلات والعملاء والموارد والماليات في مسار واضح لفريقك.':'Bring trips, customers, resources and finances together in one clear workflow for your team.',
                'برامج سياحية غنية بالتفاصيل':'Detailed travel programs','نظّم وصف الرحلة ومحتواها وخياراتها، واربطها بالمنطقة والفنادق المناسبة.':'Organize trip descriptions and options, and connect them with the right regions and hotels.','البرامج':'Programs','المناطق':'Regions','الفنادق':'Hotels',
                'أسعار وشركات وعملات':'Companies, prices and currencies','تابع الشركات والأسعار الخاصة بها والعملات المرتبطة بالتشغيل.':'Manage companies, their pricing and the currencies used in operations.','الشركات':'Companies','أسعار الشركات':'Company prices','العملات':'Currencies',
                'تشغيل يومي أكثر تنظيمًا':'A more organized daily operation','اربط الحجز بتفاصيل الرحلة والتذكرة وتوزيع المسؤوليات بين أعضاء الفريق.':'Connect bookings with trip details, tickets and clear team assignments.','الحجوزات':'Bookings','التذاكر':'Tickets','التوزيع':'Assignments',
                'موارد أسطولك ومورديك':'Fleet and supplier resources','إدارة بيانات القوارب والمركبات والسائقين والموردين والإضافات التشغيلية.':'Manage boats, vehicles, drivers, suppliers and operational extras.','قوارب':'Boats','مركبات':'Vehicles','سائقون':'Drivers','موردون':'Suppliers',
                'رؤية مالية قابلة للمتابعة':'Financial visibility you can track','انتقل من دليل الحسابات والقيود إلى الأرصدة والقوائم والتقارير المالية.':'Follow finances from the chart of accounts and journal entries through balances, statements and reports.','القيود':'Entries','دفتر الأستاذ':'Ledger','التقارير':'Reports','إدارة المستخدمين والصلاحيات':'Manage users and permissions','نظّم أدوار الحسابات وصلاحيات الوصول بما يناسب مساحة كل مستخدم.':'Set account roles and access permissions to fit each user workspace.','الإدارة':'Administration','الموظفون':'Staff','الصلاحيات':'Permissions',
                'كل مستخدم إلى مساحته':'A workspace for every role','بوابات واضحة للأدوار المختلفة':'Clear sign-in portals for each role','ابدأ من بوابة الدخول المناسبة لك؛ كل مساحة مرتبطة بمسار العمل الخاص بدورها.':'Choose the sign-in portal for your role; each workspace follows its own workflow.','إدارة الشركة':'Company management','بوابة الإدارة':'Admin portal','إدارة الرحلات والموارد والمستخدمين ومراجعة التقارير من لوحة تحكم الشركة.':'Manage trips, resources and users, and review reports from the company dashboard.','الدخول إلى الإدارة':'Go to administration','فريق التشغيل':'Operations team','بوابة الموظفين':'Staff portal','الوصول للعمليات والرحلات المسندة ومهام الفريق وفق صلاحيات الحساب.':'Access operations, assigned trips and team tasks based on account permissions.','الدخول إلى مساحة العمل':'Open workspace','إدارة المنصة':'Platform management','بوابة مدير النظام':'System admin portal','إدارة الشركات المستأجرة ومستخدميها وصلاحيات المنصة المركزية.':'Manage tenant companies, their users and central platform permissions.','الدخول لمدير النظام':'Go to system admin','تختلف البيانات والإجراءات المتاحة حسب الحساب والصلاحيات الممنوحة له.':'Available data and actions depend on each account and its assigned permissions.',
                'واجهة موقعك، متصلة بعملياتك':'Your website, connected to operations','اربط موقع شركتك بمنصة التشغيل':'Connect your company website to the operations platform','توفر MarinaX واجهة API لمواقع الشركات لعرض البرامج والشركات والعملات، وإنشاء الحجوزات ومتابعة حالتها بصلاحيات مخصصة لكل تكامل.':'MarinaX provides an API for company websites to display programs, companies and currencies, create bookings and track their status with permissions for each integration.','بيانات رحلات عامة مهيّأة لموقع الشركة':'Public trip data ready for your website','مفاتيح API بنطاقات وصلاحيات محددة':'API keys with defined scopes and permissions','حجز جديد يبدأ بحالة «قيد الانتظار»':'New bookings start as “Pending”','استكشف توثيق واجهة API':'Explore the API documentation','مسارات واجهة الموقع':'Website API routes','نقاط اتصال متاحة للتكامل':'Available integration endpoints','مصادقة وصلاحيات مرتبطة بالشركة':'Company-scoped authentication and permissions','الأسعار تُحتسب من بيانات الخادم، ومفتاح التكرار يساعد على تجنب إنشاء الحجز نفسه مرتين عند إعادة الطلب.':'Prices are calculated from server data, and an idempotency key helps prevent duplicate bookings when a request is retried.',
                'أسئلة قبل أن تبدأ':'Before you get started','إجابات سريعة وواضحة':'Quick answers','نظرة مختصرة على طريقة تنظيم المنصة والوصول إلى الوظائف الأساسية.':'A quick guide to how the platform is organized and how to access its main features.','هل يمكن إدارة أكثر من شركة على المنصة؟':'Can the platform manage more than one company?','نعم. تدعم المنصة شركات مستقلة، مع فصل بيانات الشركة وربط مستخدميها ومواردها ضمن نطاقها.':'Yes. The platform supports independent companies, keeping each company’s data separate and connecting its users and resources within its own scope.','من أين أدخل إلى النظام؟':'Where do I sign in?','اختر بوابة الإدارة أو الموظفين أو مدير النظام أعلاه. إذا لم يكن لديك حساب، تواصل مع مسؤول شركتك لتحديد الوصول المناسب.':'Choose the admin, staff or system admin portal above. If you do not have an account, contact your company administrator to arrange access.','هل يمكن ربط موقع الشركة بالرحلات والحجوزات؟':'Can I connect a company website to trips and bookings?','تتضمن المنصة API للمواقع لقراءة بيانات الرحلات والشركات والعملات وإنشاء الحجوزات ومتابعتها. راجع التوثيق لمعرفة الطلبات والصلاحيات المطلوبة.':'The website API can read trip, company and currency data, create bookings and track them. See the documentation for required requests and permissions.','هل يمكن منع تكرار الحجز عند إعادة الطلب؟':'Can duplicate bookings be prevented when retrying a request?','يساعد مفتاح التكرار على تجنب إنشاء الحجز نفسه مرتين عند إعادة الطلب.':'An idempotency key helps prevent creating the same booking twice when a request is retried.','هل تظهر كل الوظائف لكل المستخدمين؟':'Can every user see all features?','لا، الوظائف المتاحة تختلف بحسب مساحة المستخدم ودوره والصلاحيات المسندة إلى حسابه.':'No. Available features depend on the user’s workspace, role and assigned permissions.','جاهز لإدارة يومك بطريقة أوضح؟':'Ready to run your day with more clarity?','ادخل إلى مساحة عملك وتابع عمليات شركتك من منصة MarinaX.':'Open your workspace and follow your company’s operations with MarinaX.','دخول الموظفين والإدارة':'Staff and admin sign in','منصة لإدارة الأعمال السياحية والعمليات اليومية.':'A platform for managing travel businesses and daily operations.','دخول الإدارة':'Admin sign in','مدير النظام':'System admin'
            };
            Object.assign(english, {
                'لوحات التحكم': 'Dashboards',
                'وصول سريع': 'Quick access',
                'لوحات التحكم، في مكان واحد': 'Your dashboards, all in one place',
                'اختَر مساحة عملك، وسنوصلك للوحة المناسبة أو لصفحة الدخول إذا لم تكن مسجّلًا.': 'Choose your workspace. If you are not signed in, we will take you to the right sign-in page.',
                'لوحة تحكم الإدارة': 'Admin dashboard',
                'الرحلات والموارد والمستخدمون والتقارير، كلها في مساحة إدارة شركتك.': 'Manage trips, resources, users and reports in your company workspace.',
                'فتح لوحة الإدارة': 'Open admin dashboard',
                'لوحة تحكم الموظفين': 'Staff dashboard',
                'تابع العمليات والرحلات المسندة ومهام الفريق من مساحة العمل اليومية.': 'Track operations, assigned trips and team tasks in your daily workspace.',
                'فتح لوحة الموظفين': 'Open staff dashboard',
                'اختر لوحة الإدارة أو الموظفين أعلاه. إذا لم يكن لديك حساب، تواصل مع مسؤول شركتك لتحديد الوصول المناسب.': 'Choose the admin or staff dashboard above. If you do not have an account, contact your company administrator to arrange access.',
                'تظهر البيانات والإجراءات المتاحة حسب دور حسابك والصلاحيات الممنوحة له.': 'Available information and actions depend on your account role and assigned permissions.'
            });

            if (document.documentElement.lang === 'en') {
                const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
                while (walker.nextNode()) {
                    const node = walker.currentNode;
                    const key = node.nodeValue.trim();
                    if (english[key]) node.nodeValue = node.nodeValue.replace(key, english[key]);
                }
                document.querySelectorAll('[aria-label]').forEach(el => {
                    if (english[el.getAttribute('aria-label')]) el.setAttribute('aria-label', english[el.getAttribute('aria-label')]);
                });
                document.querySelector('html').setAttribute('lang', 'en');
            }

            const themeToggle = document.querySelector('[data-theme-toggle]');
            const themeColor = document.querySelector('meta[name="theme-color"]');
            const updateThemeToggle = () => {
                const isDark = document.documentElement.dataset.theme === 'dark';
                const label = document.documentElement.lang === 'en'
                    ? (isDark ? 'Switch to light mode' : 'Switch to dark mode')
                    : (isDark ? 'تفعيل الوضع الفاتح' : 'تفعيل الوضع الداكن');

                themeToggle.setAttribute('aria-label', label);
                themeToggle.setAttribute('title', label);
                themeToggle.setAttribute('aria-pressed', String(isDark));
                themeColor.setAttribute('content', isDark ? '#080f1c' : '#f4f6fa');
            };

            updateThemeToggle();
            themeToggle.addEventListener('click', () => {
                const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
                document.documentElement.dataset.theme = nextTheme;
                window.localStorage.setItem('marinax-theme', nextTheme);
                updateThemeToggle();
            });

            if (!reducedMotion && 'IntersectionObserver' in window) {
                const revealItems = document.querySelectorAll(
                    '.feature, .expanded-card, .workspace-card, .workflow-step, .trust-item, .faq-item'
                );
                revealItems.forEach((item, index) => {
                    if (item.classList.contains('workflow-step')) {
                        item.style.setProperty('--step-index', index % 4);
                    }
                });

                const revealObserver = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            revealObserver.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.12, rootMargin: '0px 0px -35px' });

                revealItems.forEach(item => revealObserver.observe(item));
            } else {
                document.querySelectorAll(
                    '.feature, .expanded-card, .workspace-card, .workflow-step, .trust-item, .faq-item'
                ).forEach(item => item.classList.add('is-visible'));
            }

            if (!reducedMotion && window.matchMedia('(pointer: fine)').matches) {
                const preview = document.querySelector('.preview');
                const previewWrap = document.querySelector('.preview-wrap');
                if (preview && previewWrap) {
                    previewWrap.addEventListener('pointermove', event => {
                        const rect = previewWrap.getBoundingClientRect();
                        const x = (event.clientX - rect.left) / rect.width - 0.5;
                        const y = (event.clientY - rect.top) / rect.height - 0.5;
                        preview.style.transform =
                            `perspective(1200px) rotateY(${(-4 + x * 5).toFixed(2)}deg) rotateX(${(1 - y * 5).toFixed(2)}deg)`;
                    });
                    previewWrap.addEventListener('pointerleave', () => {
                        preview.style.transform = 'perspective(1200px) rotateY(-4deg) rotateX(1deg)';
                    });
                }
            }

            document.querySelectorAll('.faq-item').forEach(item => {
                item.addEventListener('toggle', () => {
                    if (!item.open) return;
                    document.querySelectorAll('.faq-item[open]').forEach(openItem => {
                        if (openItem !== item) openItem.open = false;
                    });
                });
            });

        })();
    </script>
</body>
</html>
