@extends('superadmin.layout')

@section('title', ['dashboard' => 'لوحة تحكم سوبر فايزر', 'companies' => 'إدارة الشركات', 'company-apis' => 'واجهات API الشركات', 'create-company' => 'إضافة شركة', 'create-user' => 'إضافة مستخدم', 'users' => 'إدارة المستخدمين'][$page] ?? 'لوحة سوبر فايزر')
@section('page-title', ['dashboard' => 'لوحة التحكم', 'companies' => 'إدارة الشركات', 'company-apis' => 'واجهات API الشركات', 'create-company' => 'إضافة شركة', 'create-user' => 'إضافة مستخدم', 'users' => 'إدارة المستخدمين'][$page] ?? 'لوحة سوبر فايزر')

@section('body')
    <style>
        :root{color-scheme:light;--ink:#172b4d;--muted:#66758b;--line:#e7edf5;--blue:#245ca6;--surface:#fff}
        .tenant-page{max-width:none;margin:0;padding:0 0 24px}
        .tenant-header{position:relative;overflow:hidden;display:flex;justify-content:space-between;align-items:center;gap:24px;background:linear-gradient(118deg,#142746 0%,#1b4779 58%,#2866a7 100%);color:#fff;border-radius:22px;padding:32px 36px;box-shadow:0 18px 42px #17365b24}
        .tenant-header:after{content:"";position:absolute;width:330px;height:330px;border:1px solid #ffffff18;border-radius:50%;left:10%;top:-210px;box-shadow:0 0 0 42px #ffffff08,0 0 0 84px #ffffff06;pointer-events:none}
        .tenant-header-copy,.tenant-header-actions{position:relative;z-index:1}
        .tenant-eyebrow{display:flex;align-items:center;gap:8px;margin-bottom:10px;color:#c5d9f3;font-size:12px;font-weight:750;letter-spacing:.04em}
        .tenant-eyebrow:before{content:"";width:8px;height:8px;border-radius:50%;background:#79d7ad;box-shadow:0 0 0 4px #79d7ad22}
        .tenant-header h1{margin:0;font-size:clamp(23px,3vw,32px);letter-spacing:-.03em}
        .tenant-header p{max-width:650px;margin:9px 0 0;color:#d2e0f2;font-size:14px;line-height:1.8}
        .tenant-header-actions{display:flex;flex-wrap:wrap;gap:9px;align-items:center}
        .tenant-header-actions a{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:10px 15px;border:1px solid #ffffff35;border-radius:10px;background:#ffffff12;color:#fff;text-decoration:none;font:inherit;font-size:13px;font-weight:750;transition:background .18s,transform .18s}
        .tenant-header-actions a.primary{border-color:#fff;background:#fff;color:#173c6a}
        .tenant-header-actions a:hover{background:#ffffff25;transform:translateY(-1px)}
        .tenant-header-actions a.primary:hover{background:#eef5ff}
        .section-kicker{margin:24px 2px 11px;color:#8290a3;font-size:11px;font-weight:800;letter-spacing:.08em}
        .tenant-stats{display:grid;grid-template-columns:repeat(7,minmax(130px,1fr));gap:12px;margin:0 0 20px}
        .tenant-stat,.tenant-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;box-shadow:0 6px 20px #142c4b08}
        .tenant-stat{position:relative;overflow:hidden;min-height:118px;padding:17px}
        .tenant-stat:after{content:"";position:absolute;inset:auto -12px -30px auto;width:80px;height:80px;border-radius:50%;background:var(--stat-tint,#edf4fc)}
        .tenant-stat-label{position:relative;z-index:1;display:flex;align-items:center;gap:8px;color:#68778c;font-size:12px;font-weight:700}
        .tenant-stat-icon{display:grid;place-items:center;width:27px;height:27px;border-radius:8px;background:var(--stat-tint,#edf4fc);color:var(--stat-color,#245ca6);font-size:13px}
        .tenant-stat strong{position:relative;z-index:1;display:block;margin-top:12px;color:var(--stat-color,#183961);font-size:29px;line-height:1}
        .tenant-overview{display:grid;grid-template-columns:minmax(0,1fr);gap:16px;margin:18px 0 22px}
        .tenant-card{padding:22px;margin:18px 0;scroll-margin-top:16px}
        .tenant-overview .tenant-card{margin:0}
        .tenant-card-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:17px}
        .tenant-card-heading h2,.tenant-card h2{margin:0;color:#1b3558;font-size:18px;letter-spacing:-.02em}
        .tenant-card-heading p{margin:5px 0 0;color:#8490a2;font-size:12px}
        .tenant-card-link{color:#245ca6;font-size:12px;font-weight:750;text-decoration:none;white-space:nowrap}
        .tenant-card-link:hover{text-decoration:underline}
        .recent-users{display:grid;gap:2px}
        .recent-user{display:grid;grid-template-columns:minmax(0,1fr) auto auto;align-items:center;gap:14px;padding:11px 0;border-top:1px solid #eef2f7}
        .recent-user-name{overflow:hidden;color:#263a58;font-size:13px;font-weight:750;text-overflow:ellipsis;white-space:nowrap}
        .recent-user-email{display:block;overflow:hidden;margin-top:3px;color:#8a96a7;font-size:11px;text-overflow:ellipsis;white-space:nowrap}
        .recent-user-company{max-width:145px;overflow:hidden;color:#66758b;font-size:12px;text-overflow:ellipsis;white-space:nowrap}
        .company-permissions-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr));gap:13px}
        .company-permissions-item{min-width:0;overflow:hidden;border:1px solid #e7edf5;border-radius:14px;background:linear-gradient(145deg,#fff,#fbfcfe)}
        .company-permissions-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:15px 16px 13px;border-bottom:1px solid #edf1f6}
        .company-permissions-identity{min-width:0}
        .company-permissions-name{display:block;overflow:hidden;color:#223a5e;font-size:14px;font-weight:800;text-overflow:ellipsis;white-space:nowrap}
        .company-permissions-meta{display:flex;flex-wrap:wrap;align-items:center;gap:7px;margin-top:8px;color:#718096;font-size:11px}
        .company-permissions-count{display:inline-flex;align-items:center;min-height:24px;padding:4px 8px;border:1px solid #edf1f6;border-radius:7px;background:#fbfcfe}
        .permission-mode{display:inline-flex;flex:0 0 auto;align-items:center;min-height:25px;padding:4px 9px;border:1px solid #dce8f5;border-radius:99px;background:#edf4fc;color:#245ca6;font-size:10px;font-weight:800}
        .permission-mode.managed{border-color:#f5e5c4;background:#fff8e8;color:#9a6700}
        .permission-mode.none{border-color:#e7edf5;background:#f8fafc;color:#718096}
        .company-permissions-summary{padding:13px 16px 15px}
        .company-permissions-summary-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;color:#66758b;font-size:11px}
        .company-permissions-summary-head strong{color:#1b3558;font-size:12px}
        .permission-modules{display:grid;gap:8px}
        .permission-module{padding:9px 10px;border:1px solid #edf1f6;border-radius:9px;background:#fff}
        .permission-module-head{display:flex;align-items:baseline;justify-content:space-between;gap:8px}
        .permission-module-head strong{min-width:0;overflow:hidden;color:#344054;font-size:12px;text-overflow:ellipsis;white-space:nowrap}
        .permission-module-key{flex:0 0 auto;color:#98a2b3;font-size:10px;direction:ltr}
        .permission-actions{display:flex;flex-wrap:wrap;gap:5px;margin-top:7px}
        .permission-action{display:inline-flex;align-items:center;padding:3px 7px;border-radius:6px;background:#f1f6fc;color:#526b88;font-size:10px}
        .permission-preview{display:flex;flex-wrap:wrap;gap:7px}
        .permission-chip{display:inline-flex;max-width:100%;align-items:center;gap:6px;padding:6px 9px;border:1px solid #e3ebf5;border-radius:8px;background:#fff;color:#526b88;font-size:10px;line-height:1.35}
        .permission-chip strong{overflow:hidden;max-width:150px;color:#344054;text-overflow:ellipsis;white-space:nowrap}
        .permission-chip span{color:#245ca6;font-weight:800}
        .permission-extra[hidden]{display:none}
        .permission-show-more{display:inline-flex;align-items:center;justify-content:center;min-height:32px;margin-top:9px;padding:6px 10px;border:1px solid #d7e3f2;border-radius:8px;background:#f8fbff;color:#245ca6;font:inherit;font-size:11px;font-weight:800;cursor:pointer}
        .permission-show-more:hover{border-color:#a9c3df;background:#eef5ff}
        .permission-modules[hidden]{display:none}
        .permission-toggle{display:inline-flex;align-items:center;justify-content:center;margin-top:10px;padding:8px 12px!important;border:1px solid #dce8f5!important;border-radius:8px!important;background:#f6f9fd!important;color:#245ca6!important;font-size:11px!important;font-weight:800!important}
        .permission-toggle:hover{border-color:#b9cee8!important;background:#edf5ff!important;transform:translateY(-1px)}
        .permission-empty{padding:13px 8px;border:1px dashed #dce4ee;border-radius:9px;color:#8793a4;font-size:11px;text-align:center}
        .empty-note{padding:20px 10px;color:#8793a4;font-size:13px;text-align:center}
        .tenant-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px}
        .tenant-grid label,.tenant-field{display:grid;gap:6px;color:#45546a;font-size:13px;font-weight:650}
        .tenant-grid input,.tenant-grid select,.user-edit-form input,.user-edit-form select,.filter-select{width:100%;min-height:40px;padding:9px 11px;border:1px solid #d5deea;border-radius:9px;background:#fff;color:#172033;font:inherit;font-size:13px;transition:border-color .15s,box-shadow .15s}
        .tenant-grid input:focus,.tenant-grid select:focus,.user-edit-form input:focus,.user-edit-form select:focus,.filter-select:focus{outline:0;border-color:#5f94d0;box-shadow:0 0 0 3px #2c69ae18}
        .tenant-card button{min-height:39px;padding:9px 15px;border:0;border-radius:9px;background:#245ca6;color:#fff;font:inherit;font-size:13px;font-weight:750;cursor:pointer;transition:background .18s,transform .18s}
        .tenant-card button:hover{background:#194b8b;transform:translateY(-1px)}
        .tenant-form-actions{display:flex;align-items:center;gap:12px;margin-top:14px}
        .tenant-form-note{color:#8793a4;font-size:12px}
        .company-list{display:grid;gap:12px}
        .company-item{padding:16px;border:1px solid #e8edf4;border-radius:13px;background:#fcfdff}
        .company-item-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:13px}
        .company-item-head strong{color:#223a5e;font-size:14px}
        .tenant-status{display:inline-flex;align-items:center;gap:6px;border-radius:99px;padding:5px 10px;font-size:11px;font-weight:800;white-space:nowrap}
        .tenant-status:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}
        .tenant-status.active{background:#e9f8ef;color:#13804b}
        .tenant-status.suspended{background:#fff1ed;color:#b54726}
        .company-form{display:grid;grid-template-columns:repeat(5,minmax(140px,1fr));gap:11px;align-items:end;margin:0}
        .company-form label{display:grid;gap:6px;color:#65748a;font-size:11px;font-weight:700}
        .company-form input,.company-form select{min-width:0;width:100%;height:38px;padding:8px 10px;border:1px solid #dce4ee;border-radius:8px;background:#fff;color:#24364e;font:inherit;font-size:12px}
        .company-form .company-save{display:flex;align-items:center;justify-content:space-between;gap:10px;grid-column:1/-1}
        .company-users-count{color:#738197;font-size:12px}
        .api-company-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,500px),1fr));gap:14px}
        .api-company-card{overflow:hidden;padding:0;border:1px solid #e3eaf3;border-radius:15px;background:#fff;box-shadow:0 5px 18px #17365b09;transition:border-color .18s,box-shadow .18s}
        .api-company-card:hover{border-color:#cddced;box-shadow:0 9px 24px #17365b10}
        .api-company-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 17px;border-bottom:1px solid #edf1f6;background:#fbfcfe}
        .api-company-identity{display:flex;min-width:0;align-items:center;gap:11px}
        .api-company-avatar{display:grid;width:38px;height:38px;flex:0 0 38px;place-items:center;border:1px solid #dce8f5;border-radius:12px;background:#edf4fc;color:#245ca6;font-size:16px;font-weight:850}
        .api-company-head strong{overflow:hidden;color:#223a5e;font-size:15px;text-overflow:ellipsis;white-space:nowrap}
        .api-company-content{display:grid;gap:13px;padding:15px 17px}
        .api-company-state{display:flex;flex-wrap:wrap;align-items:center;gap:7px;margin:0;color:#66758b;font-size:11px}
        .api-company-state>span:not(.api-key-status){display:inline-flex;align-items:center;min-height:27px;padding:5px 9px;border:1px solid #edf1f6;border-radius:8px;background:#fbfcfe}
        .api-key-status{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border:1px solid #dcefe4;border-radius:99px;background:#f0faf4;color:#13804b;font-size:10px;font-weight:800}
        .api-key-status:before{width:6px;height:6px;border-radius:50%;background:currentColor;content:""}
        .api-key-status.is-expired,.api-key-status.is-missing{border-color:#f5dfd5;background:#fff7f2;color:#b54726}
        .api-company-keybox{padding:11px;border:1px solid #e4ebf3;border-radius:11px;background:#f8fafc}
        .api-company-key-label{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px;color:#66758b;font-size:11px;font-weight:800}
        .api-company-key-row{display:flex;align-items:stretch;gap:8px}
        .api-company-key-value{display:flex;min-width:0;min-height:43px;flex:1;align-items:center;overflow-wrap:anywhere;padding:10px 11px;border:1px solid #e1e8f0;border-radius:9px;background:#fff;color:#17365b;direction:ltr;text-align:left;font-size:11px;line-height:1.55;user-select:all}
        .api-company-key-row .api-copy-button{display:inline-flex;min-height:43px!important;align-items:center;justify-content:center;gap:7px;padding:8px 11px!important;border-color:#cddced!important;border-radius:9px!important;background:#fff!important;color:#245ca6!important;font-size:11px!important;font-weight:800!important;white-space:nowrap}
        .api-company-key-row .api-copy-button:hover{border-color:#8fb1d9!important;background:#edf5ff!important}
        .api-company-key-row .api-copy-button svg{width:15px;height:15px}
        .api-key-prefix{display:block;overflow-wrap:anywhere;margin:8px 0;padding:10px 12px;border:1px solid #e7edf5;border-radius:8px;background:#fff;color:#245ca6;direction:ltr;text-align:left;font-size:12px}
        .api-key-copy-row{display:flex;flex-wrap:wrap;align-items:center;gap:10px}
        .api-key-copy-row .api-key-prefix{flex:1;margin:8px 0}
        .api-key-copy-status{min-height:20px;color:#13804b;font-size:12px}
        .api-copy-button{min-height:34px!important;padding:7px 10px!important;border:1px solid #d5deea!important;background:#fff!important;color:#245ca6!important;font-size:11px!important}
        .api-copy-button:hover{border-color:#b9cee8!important;background:#f6f9fd!important}
        .api-endpoint-link{justify-content:space-between}
        .api-endpoint-link code{flex:1}
        .api-overview{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:18px 0}
        .api-overview-card{display:flex;min-width:0;align-items:center;gap:13px;padding:17px;border:1px solid #e4ebf3;border-radius:15px;background:linear-gradient(145deg,#fff,#f8fafd);box-shadow:0 6px 18px #142c4b08}
        .api-overview-icon{display:grid;width:42px;height:42px;flex:0 0 42px;place-items:center;border:1px solid #e2ebf6;border-radius:13px;background:#edf4fc;color:#245ca6;font-size:18px}
        .api-overview-card.is-active .api-overview-icon{border-color:#d9f0e2;background:#effaf3;color:#13804b}
        .api-overview-card.is-expired .api-overview-icon{border-color:#f7e5d7;background:#fff7f2;color:#b54726}
        .api-overview-copy{display:grid;gap:3px}
        .api-overview-copy span{color:#718096;font-size:11px;font-weight:700}
        .api-overview-copy strong{color:#1b3558;font-size:23px;line-height:1}
        .api-management-tools{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin:18px 0}
        .api-company-search{position:relative;display:flex;min-width:min(100%,300px);max-width:440px;flex:1;align-items:center;gap:9px;padding:0 13px;border:1px solid #dce4ee;border-radius:11px;background:#fff;transition:border-color .15s,box-shadow .15s}
        .api-company-search:focus-within{border-color:#5f94d0;box-shadow:0 0 0 3px #2c69ae18}
        .api-company-search svg{width:17px;height:17px;flex:0 0 17px;color:#8290a3}
        .api-company-search input{width:100%;min-width:0;min-height:43px;padding:0;border:0;outline:0;background:transparent;color:#24364e;font:inherit;font-size:13px}
        .api-management-links{display:flex;flex-wrap:wrap;gap:8px}
        .api-management-link{display:inline-flex;min-height:39px;align-items:center;justify-content:center;gap:7px;padding:8px 12px;border:1px solid #dce4ee;border-radius:9px;background:#fff;color:#245ca6;text-decoration:none;font-size:12px;font-weight:800;transition:background .15s,border-color .15s}
        .api-management-link:hover{border-color:#b9cee8;background:#f6f9fd}
        .api-endpoint-meta{display:grid;gap:4px;min-width:0;flex:1}
        .api-endpoint-scope{color:#8a96a7;font-size:10px;direction:ltr;text-align:start}
        .api-endpoint-link code{min-width:0;overflow-wrap:anywhere;direction:ltr;text-align:left}
        .api-endpoint-link .api-method{display:inline-flex;min-width:47px;justify-content:center;padding:4px 6px;border-radius:6px;background:#edf4fc;color:#245ca6;font-size:9px;font-weight:900;direction:ltr}
        .api-endpoint-link .api-method.post{background:#eaf8ef;color:#13804b}
        .api-endpoint-link .api-method.patch{background:#fff7e6;color:#9a6700}
        .api-no-results{padding:22px;border:1px dashed #d7e0eb;border-radius:12px;color:#718096;text-align:center;font-size:13px}
        .api-no-results[hidden]{display:none}
        .api-filter-count{margin:-5px 0 14px;color:#8793a4;font-size:11px}
        .api-docs{padding:18px;border:1px solid #e4ebf3;border-radius:13px;background:#f8fafc}
        .api-docs h3{margin:0;color:#1b3558;font-size:15px}
        .api-docs p{margin:7px 0;color:#66758b;font-size:12px;line-height:1.75}
        .api-docs .api-auth-sample{display:block;overflow-wrap:anywhere;margin:10px 0;padding:10px 12px;border:1px solid #e1e8f0;border-radius:9px;background:#fff;color:#17365b;direction:ltr;text-align:left;font-size:11px}
        .api-company-card[hidden]{display:none}
        @media(max-width:700px){.api-overview{grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.api-overview-card{align-items:flex-start;flex-direction:column;gap:9px;padding:12px}.api-overview-icon{width:35px;height:35px;flex-basis:35px;border-radius:10px}.api-overview-copy strong{font-size:20px}.api-management-tools{align-items:stretch;flex-direction:column}.api-company-search{max-width:none}.api-management-links>*{flex:1}.api-scope-options{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:440px){.api-overview{grid-template-columns:1fr}.api-overview-card{align-items:center;flex-direction:row}.api-endpoint-link{align-items:flex-start}.api-endpoint-meta{flex-basis:calc(100% - 65px)}.api-endpoint-link .api-copy-button{margin-inline-start:auto}.api-scope-options{grid-template-columns:1fr}.api-expiry-form>button{width:100%}}
        .api-success-overlay{position:fixed;inset:0;z-index:1000;display:grid;place-items:center;padding:20px;background:#10243f99;backdrop-filter:blur(5px)}
        .api-success-modal{position:relative;width:min(560px,100%);max-height:min(90vh,780px);overflow:auto;padding:28px;border:1px solid #e8edf5;border-radius:22px;background:#fff;box-shadow:0 24px 80px #10243f40}
        .api-success-icon{display:grid;width:58px;height:58px;place-items:center;margin-bottom:16px;border-radius:18px;background:#e9f8ef;color:#13804b;font-size:30px}
        .api-success-modal h2{margin:0;color:#17365b;font-size:23px}
        .api-success-modal>p{margin:8px 0 18px;color:#66758b;font-size:13px;line-height:1.7}
        .api-success-label{display:block;margin:14px 0 6px;color:#475467;font-size:12px;font-weight:800}
        .api-success-key{display:block;overflow-wrap:anywhere;padding:13px;border:1px solid #dce5f0;border-radius:10px;background:#f8fafc;color:#17365b;direction:ltr;text-align:left;font-size:12px}
        .api-success-url{display:flex;align-items:center;gap:9px}
        .api-success-key-row{display:flex;align-items:stretch;gap:9px}
        .api-success-key-row .api-success-key{min-width:0;flex:1}
        .api-success-key-row .api-copy-button{display:inline-flex;min-height:44px!important;align-items:center;justify-content:center;gap:8px;padding:8px 14px!important;border:1px solid #cddced!important;border-radius:9px!important;background:#fff!important;color:#245ca6!important;font:inherit;font-size:12px!important;font-weight:800!important;white-space:nowrap;transition:border-color .18s,background .18s,box-shadow .18s}
        .api-success-key-row .api-copy-button:hover{transform:none;border-color:#9bb8dc!important;background:#f3f8ff!important;color:#194b8b!important}
        .api-success-key-row .api-copy-button:focus-visible{outline:0;box-shadow:0 0 0 3px #2c69ae24}
        .api-success-key-row .api-copy-button:active{transform:translateY(1px)}
        .api-success-key-row .api-copy-button svg{width:16px;height:16px;flex:0 0 16px}
        .api-success-url code{min-width:0;flex:1;overflow-wrap:anywhere;padding:11px;border:1px solid #e7edf5;border-radius:9px;background:#f8fafc;direction:ltr;text-align:left}
        .api-success-actions{display:flex;flex-wrap:wrap;gap:9px;margin-top:18px}
        .api-success-actions .secondary{border:1px solid #dce4ee;background:#fff;color:#344054}
        .api-success-note{margin-top:14px!important;color:#8a5b08!important;font-size:11px!important}
        .api-success-close{position:absolute;top:15px;inset-inline-end:15px;display:grid;width:36px;height:36px;place-items:center;border:1px solid #e7edf5;border-radius:10px;background:#fff;color:#66758b;font-size:20px;cursor:pointer}
        .api-copy-toast{position:fixed;right:24px;bottom:24px;z-index:1100;padding:12px 18px;border:1px solid #b7ebc9;border-radius:12px;background:#ecfdf3;color:#067647;font-size:13px;font-weight:750;box-shadow:0 10px 28px #142c4b20}
        .api-copy-toast[hidden]{display:none}
        .api-expiry-form{display:grid;grid-template-columns:minmax(0,1fr);align-items:start;gap:11px;margin:0;padding:13px;border:1px solid #edf1f6;border-radius:11px;background:#fff}
        .api-expiry-form>label{display:grid;width:min(100%,270px);gap:6px;color:#66758b;font-size:11px;font-weight:750}
        .api-expiry-form input[type=date]{min-width:0;height:39px;padding:8px 10px;border:1px solid #dce4ee;border-radius:8px;background:#fff;color:#24364e;font:inherit;font-size:12px}
        .api-scope-options{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:7px;min-width:0;margin:0;padding:10px;border:1px solid #e8edf4;border-radius:9px;background:#fbfcfe}
        .api-scope-options legend{padding:0 5px;color:#66758b;font-size:11px;font-weight:800}
        .api-scope-options label{display:flex;min-width:0;min-height:34px;align-items:center;gap:7px;padding:6px 8px;border:1px solid #e8edf4;border-radius:7px;background:#fff;color:#475467;font-size:10px;font-weight:650;direction:ltr}
        .api-scope-options input[type=checkbox]{width:15px;height:15px;flex:0 0 15px;margin:0;accent-color:#245ca6}
        .api-expiry-form>button{justify-self:start;min-width:150px}
        .api-company-scopes{margin:0;color:#77859a;font-size:10px;line-height:1.6}
        .api-actions{display:flex;flex-wrap:wrap;gap:8px}
        .api-actions form{margin:0}
        .api-endpoints-section{display:grid;gap:9px;padding-top:10px;border-top:1px solid #edf1f6}
        .api-endpoints-heading{display:flex;align-items:center;justify-content:space-between;gap:10px;color:#344054;font-size:12px;font-weight:850;cursor:pointer;list-style:none}
        .api-endpoints-heading::-webkit-details-marker{display:none}
        .api-endpoints-heading:after{content:"＋";display:grid;width:23px;height:23px;place-items:center;border:1px solid #e4ebf3;border-radius:7px;color:#66758b;font-size:14px}
        .api-endpoints-section[open] .api-endpoints-heading:after{content:"−"}
        .api-endpoint-list{display:grid;gap:7px}
        .api-endpoint-link{display:flex;flex-wrap:wrap;align-items:center;gap:8px;padding:8px 10px;border:1px solid #e7edf5;border-radius:8px;background:#fff;color:#245ca6;text-decoration:none}
        .api-endpoint-link:hover{border-color:#b9cee8;background:#f6f9fd}
        .api-endpoint-link strong{color:#66758b;font-size:10px}
        .api-endpoint-link code{overflow-wrap:anywhere;font-size:11px}
        @media(max-width:520px){.api-company-content{padding:14px}.api-company-head{padding:14px}.api-company-key-row,.api-success-key-row{flex-direction:column}.api-company-key-row .api-copy-button,.api-success-key-row .api-copy-button{width:100%}.api-expiry-form{grid-template-columns:1fr}.api-expiry-form button{width:100%}}
        .api-docs{margin-top:20px}
        .api-docs code{direction:ltr}
        .api-endpoints{display:flex;flex-wrap:wrap;gap:8px}
        .api-endpoints code{padding:7px 9px;border-radius:7px;background:#f2f5f9;color:#344054}
        .tenant-table-wrap{overflow:auto;border:1px solid #e9eef5;border-radius:12px}
        .tenant-table{width:100%;border-collapse:collapse}
        .tenant-table th,.tenant-table td{padding:12px;text-align:start;border-bottom:1px solid #edf1f6;vertical-align:middle}
        .tenant-table th{background:#f8fafc;color:#718096;font-size:11px;font-weight:800;white-space:nowrap}
        .tenant-table td{color:#35465f;font-size:12px}
        .tenant-table tbody tr:last-child td{border-bottom:0}
        .user-primary{color:#203754;font-size:13px;font-weight:750}
        .user-secondary{display:block;margin-top:3px;color:#8995a6;font-size:11px}
        .user-edit summary{color:#245ca6;font-size:12px;font-weight:750;cursor:pointer;white-space:nowrap}
        .user-edit[open]{min-width:300px}
        .user-edit-form{display:grid;grid-template-columns:repeat(2,minmax(150px,1fr));gap:9px;min-width:340px;margin:12px 0 2px}
        .user-edit-form input,.user-edit-form select{min-width:0;min-height:36px;padding:7px 9px;font-size:12px}
        .user-edit-form button{grid-column:1/-1;justify-self:start}
        .tenant-filter{display:flex;flex-wrap:wrap;align-items:end;gap:9px;margin:0 0 15px}
        .tenant-filter label{display:grid;gap:6px;color:#596a80;font-size:12px;font-weight:750}
        .tenant-filter select{min-width:220px}
        .tenant-filter a{padding:10px 4px;color:#66758b;font-size:12px;font-weight:700;text-decoration:none}
        .tenant-filter a:hover{color:#245ca6}
        .tenant-success{padding:13px 16px;background:#ecfdf3;color:#067647;border:1px solid #b7ebc9;border-radius:10px;margin:16px 0}
        .tenant-error{padding:11px 14px;background:#fff1f0;color:#b42318;border:1px solid #fee4e2;border-radius:10px;margin:12px 0;font-size:13px}
        @media(max-width:1150px){.tenant-stats{grid-template-columns:repeat(4,minmax(130px,1fr))}.company-form{grid-template-columns:repeat(3,minmax(140px,1fr))}}
        @media(max-width:850px){.tenant-header{align-items:flex-start;padding:26px}.tenant-header-actions{justify-content:flex-start}.tenant-stats{grid-template-columns:repeat(3,minmax(120px,1fr))}}
        @media(max-width:600px){.tenant-header{display:grid;gap:20px;padding:21px;border-radius:17px}.tenant-header p{font-size:13px}.tenant-header-actions a{flex:1}.tenant-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.tenant-stat{min-height:102px;padding:14px}.tenant-stat strong{font-size:25px}.tenant-card{padding:16px}.tenant-overview{gap:11px}.recent-user{grid-template-columns:minmax(0,1fr) auto;gap:8px}.recent-user-company{display:none}.company-permissions-grid{grid-template-columns:1fr}.company-permissions-head{padding:13px}.company-permissions-summary{padding:12px 13px}.company-form{grid-template-columns:repeat(2,minmax(0,1fr))}.company-form .company-save{align-items:flex-start;flex-direction:column}.user-edit-form{min-width:290px}.tenant-filter select{min-width:190px}}
    </style>
<main class="tenant-page">
    @php
        $roleLabels = ['admin' => __('مدير الشركة'), 'staff' => __('موظف'), 'accountant' => __('محاسب'), 'viewer' => __('مشاهد'), 'account_user' => __('مستخدم حسابات'), 'supervisor' => __('مشرف'), 'customer' => __('عميل')];
    @endphp
    <header class="tenant-header">
        <div class="tenant-header-copy">
            <div class="tenant-eyebrow">{{ __('إدارة المنصة') }}</div>
            <h1>{{ __(['dashboard' => 'مرحباً بك في لوحة سوبر فايزر', 'companies' => 'إدارة الشركات', 'company-apis' => 'واجهات API الشركات', 'create-company' => 'إضافة شركة جديدة', 'create-user' => 'إضافة مستخدم لشركة', 'users' => 'إدارة المستخدمين'][$page] ?? 'لوحة سوبر فايزر') }}</h1>
            <p>{{ __(['dashboard' => 'نظرة عامة على الشركات والمستخدمين وحالة الحسابات.', 'companies' => 'عرض بيانات الشركات وتحديثها وإدارة حالتها.', 'company-apis' => 'إدارة مفتاح API الخاص بكل شركة واستعراض واجهات الموقع المتاحة.', 'create-company' => 'أضف شركة جديدة إلى المنصة.', 'create-user' => 'أنشئ حساباً واربطه بإحدى الشركات النشطة.', 'users' => 'عرض حسابات المستخدمين وتعديل بياناتها وحالتها.'][$page] ?? '') }}</p>
        </div>
        <div class="tenant-header-actions">
            @if($page === 'dashboard' || $page === 'companies')
                <a class="primary" href="{{ route('superadmin.tenants.create') }}">＋ {{ __('إضافة شركة') }}</a>
            @endif
            @if($page === 'dashboard' || $page === 'users')
                <a href="{{ route('superadmin.users.create') }}">＋ {{ __('إضافة مستخدم') }}</a>
            @endif
        </div>
    </header>

    @if(session('status'))
        <div class="tenant-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="tenant-error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif

    @if($page === 'dashboard')
    <div class="section-kicker">{{ __('ملخص المنصة') }}</div>
    <section class="tenant-stats" aria-label="{{ __('ملخص النظام') }}">
        <article class="tenant-stat" style="--stat-tint:#edf4fc;--stat-color:#245ca6"><span class="tenant-stat-label"><span class="tenant-stat-icon">▦</span>{{ __('إجمالي الشركات') }}</span><strong>{{ $stats['companies'] }}</strong></article>
        <article class="tenant-stat" style="--stat-tint:#e9f8ef;--stat-color:#13804b"><span class="tenant-stat-label"><span class="tenant-stat-icon">✓</span>{{ __('شركات نشطة') }}</span><strong>{{ $stats['active_companies'] }}</strong></article>
        <article class="tenant-stat" style="--stat-tint:#fff1ed;--stat-color:#b54726"><span class="tenant-stat-label"><span class="tenant-stat-icon">Ⅱ</span>{{ __('شركات موقوفة') }}</span><strong>{{ $stats['suspended_companies'] }}</strong></article>
        <article class="tenant-stat" style="--stat-tint:#edf4fc;--stat-color:#245ca6"><span class="tenant-stat-label"><span class="tenant-stat-icon">♙</span>{{ __('إجمالي المستخدمين') }}</span><strong>{{ $stats['users'] }}</strong></article>
        <article class="tenant-stat" style="--stat-tint:#e9f8ef;--stat-color:#13804b"><span class="tenant-stat-label"><span class="tenant-stat-icon">✓</span>{{ __('مستخدمون نشطون') }}</span><strong>{{ $stats['active_users'] }}</strong></article>
        <article class="tenant-stat" style="--stat-tint:#fff1ed;--stat-color:#b54726"><span class="tenant-stat-label"><span class="tenant-stat-icon">Ⅱ</span>{{ __('مستخدمون موقوفون') }}</span><strong>{{ $stats['inactive_users'] }}</strong></article>
        <article class="tenant-stat" style="--stat-tint:#fff7e6;--stat-color:#9a6700"><span class="tenant-stat-label"><span class="tenant-stat-icon">!</span>{{ __('بلا شركة') }}</span><strong>{{ $stats['unassigned_users'] }}</strong></article>
    </section>

    <section class="tenant-overview" id="overview" aria-label="{{ __('نظرة عامة') }}">
        <article class="tenant-card">
            <div class="tenant-card-heading">
                <div><h2>{{ __('صلاحيات المستخدمين') }}</h2><p>{{ __('مدير الشركة') }} · {{ __('الصلاحيات') }} · {{ __('الإجراءات') }}</p></div>
                <a class="tenant-card-link" href="{{ route('superadmin.permissions.index') }}">{{ __('صلاحيات المستخدمين') }} ←</a>
            </div>
            <div class="company-permissions-grid">
                @forelse($tenants as $tenant)
                    @php
                        $permissionMode = $tenant->permission_mode;
                        $permissionModeLabel = [
                            'full' => __('كاملة'),
                            'managed' => __('محددة'),
                            'none' => __('لا توجد'),
                        ][$permissionMode] ?? __('غير محددة');
                        $permissionEntries = collect(data_get($tenant, 'permission_modules', []))
                            ->flatMap(function ($permissionModule) {
                                $actions = collect(data_get($permissionModule, 'actions', []));

                                if ($actions->isEmpty()) {
                                    return [[
                                        'label' => data_get($permissionModule, 'label'),
                                        'module' => data_get($permissionModule, 'module'),
                                        'action' => null,
                                    ]];
                                }

                                return $actions->map(function ($action) use ($permissionModule) {
                                    return [
                                        'label' => data_get($permissionModule, 'label'),
                                        'module' => data_get($permissionModule, 'module'),
                                        'action' => $action,
                                    ];
                                })->values();
                            })
                            ->values();
                        $permissionListId = 'tenant-permissions-'.$tenant->id;
                        $visiblePermissionEntries = $permissionEntries->take(5);
                        $hiddenPermissionEntries = $permissionEntries->slice(5);
                    @endphp
                    <article class="company-permissions-item">
                        <div class="company-permissions-head">
                            <div class="company-permissions-identity">
                                <span class="company-permissions-name" title="{{ $tenant->name }}">{{ $tenant->name }}</span>
                                <span class="company-permissions-meta">
                                    <span class="tenant-status {{ $tenant->status ? 'active' : 'suspended' }}">{{ $tenant->status ? __('نشطة') : __('موقوفة') }}</span>
                                    <span class="company-permissions-count">{{ $tenant->users_count }} {{ __('مستخدم') }}</span>
                                    <span class="company-permissions-count">{{ $tenant->admin_count }} {{ __('مدير الشركة') }}</span>
                                    <span class="company-permissions-count">{{ $tenant->managed_admin_count }} {{ __('مدير') }} · {{ __('صلاحيات محددة') }}</span>
                                </span>
                            </div>
                            <span class="permission-mode {{ $permissionMode }}">{{ $permissionModeLabel }}</span>
                        </div>
                        <div class="company-permissions-summary">
                            <div class="company-permissions-summary-head">
                                <span>{{ __('الوحدات والإجراءات المصرح بها') }}</span>
                                <strong>{{ $tenant->permission_count }} {{ __('الصلاحيات') }}</strong>
                            </div>
                            <div class="permission-modules">
                                @forelse($visiblePermissionEntries as $permissionEntry)
                                    <div class="permission-module">
                                        <div class="permission-module-head">
                                            <strong>{{ $permissionEntry['label'] }}</strong>
                                            <span class="permission-module-key">{{ $permissionEntry['module'] }}</span>
                                        </div>
                                        <div class="permission-actions">
                                            @if($permissionEntry['action'] !== null)
                                                <span class="permission-action">{{ $permissionEntry['action'] }}</span>
                                            @else
                                                <span class="permission-action">{{ __('لا توجد') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="permission-empty">{{ __('لا توجد صلاحيات مخصصة لهذه الشركة.') }}</div>
                                @endforelse
                            </div>
                            @if($hiddenPermissionEntries->isNotEmpty())
                                <div id="{{ $permissionListId }}" class="permission-modules" hidden>
                                    @foreach($hiddenPermissionEntries as $permissionEntry)
                                        <div class="permission-module">
                                            <div class="permission-module-head">
                                                <strong>{{ $permissionEntry['label'] }}</strong>
                                                <span class="permission-module-key">{{ $permissionEntry['module'] }}</span>
                                            </div>
                                            <div class="permission-actions">
                                                @if($permissionEntry['action'] !== null)
                                                    <span class="permission-action">{{ $permissionEntry['action'] }}</span>
                                                @else
                                                    <span class="permission-action">{{ __('لا توجد') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="permission-toggle" data-permission-target="{{ $permissionListId }}" data-show-all="{{ __('عرض الكل') }} (+{{ $hiddenPermissionEntries->count() }})" data-show-less="{{ __('عرض أقل') }}" aria-controls="{{ $permissionListId }}" aria-expanded="false">
                                    {{ __('عرض الكل') }} (+{{ $hiddenPermissionEntries->count() }})
                                </button>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="empty-note">{{ __('لا توجد شركات') }}</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="tenant-card">
        <div class="tenant-card-heading">
            <div><h2>{{ __('أحدث الموظفين والمستخدمين') }}</h2><p>{{ __('آخر الحسابات المسجلة على المنصة') }}</p></div>
            <a class="tenant-card-link" href="{{ route('superadmin.users.index') }}">{{ __('إدارة المستخدمين ←') }}</a>
        </div>
        <div class="recent-users">
            @forelse($recentUsers as $recentUser)
                <div class="recent-user">
                    <div>
                        <span class="recent-user-name">{{ $recentUser->name }}</span>
                        <span class="recent-user-email">{{ $roleLabels[$recentUser->role] ?? $recentUser->role }} · {{ $recentUser->email }}</span>
                    </div>
                    <span class="recent-user-company">{{ $recentUser->tenant?->name ?? __('بدون شركة') }}</span>
                    <span class="tenant-status {{ $recentUser->status ? 'active' : 'suspended' }}">{{ $recentUser->status ? __('نشط') : __('موقوف') }}</span>
                </div>
            @empty
                <div class="empty-note">{{ __('لا يوجد مستخدمون مسجلون حتى الآن.') }}</div>
            @endforelse
        </div>
    </section>

    @endif

    @if($page === 'create-company')
    <section class="tenant-card">
        <h2>إضافة شركة</h2>
        <form method="post" action="{{ route('superadmin.tenants.store') }}">
            @csrf
            <div class="tenant-grid">
                <label>اسم الشركة<input name="name" required maxlength="255" value="{{ old('name') }}"></label>
                <label>البريد الإلكتروني<input name="email" type="email" value="{{ old('email') }}"></label>
                <label>الهاتف<input name="phone" value="{{ old('phone') }}"></label>
                <label>العنوان<input name="address" value="{{ old('address') }}"></label>
            </div>
            <div class="tenant-form-actions"><button type="submit">إنشاء الشركة</button><span class="tenant-form-note">سيتم تفعيل الشركة تلقائياً بعد الإنشاء.</span></div>
        </form>
    </section>
    @endif

    @if($page === 'create-user')
    <section class="tenant-card">
        <h2>إضافة مستخدم لشركة</h2>
        <form method="post" action="{{ route('superadmin.users.store') }}" autocomplete="on">
            @csrf
            <div class="tenant-grid">
                <label>الشركة<select name="tenant_id" required>@foreach($tenants->where('status', true) as $tenant)<option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>{{ $tenant->name }}</option>@endforeach</select></label>
                <label>الاسم<input name="name" required maxlength="255" value="{{ old('name') }}"></label>
                <label>اسم المستخدم<input name="username" required maxlength="255" value="{{ old('username') }}"></label>
                <label>البريد الإلكتروني<input name="email" type="email" required autocomplete="username" value="{{ old('email') }}"></label>
                <label>الدور<select name="role" required><option value="admin">مدير الشركة</option><option value="staff">موظف</option><option value="accountant">محاسب</option><option value="viewer">مشاهد</option><option value="account_user">مستخدم حسابات</option><option value="supervisor">مشرف</option><option value="customer">عميل</option></select></label>
                <label>كلمة المرور<input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
            </div>
            <div class="tenant-form-actions"><button type="submit">إنشاء المستخدم النشط</button><span class="tenant-form-note">سيكون المستخدم نشطاً فور إنشائه.</span></div>
        </form>
    </section>
    @endif

    @if($page === 'companies')
    <section class="tenant-card">
        <div class="tenant-card-heading">
            <div><h2>إدارة الشركات</h2><p>{{ $tenants->count() }} شركة مسجلة · يمكنك تعديل بيانات الشركة أو تغيير حالتها</p></div>
            <a class="tenant-card-link" href="{{ route('superadmin.tenants.create') }}">إضافة شركة جديدة ↑</a>
        </div>
            <div class="tenant-card">
                <h3>واجهات مواقع الشركات</h3>
                <p>أرسل المفتاح في ترويسة <code dir="ltr">Authorization: Bearer YOUR_API_KEY</code>.</p>
                <p dir="ltr"><code>GET /api/v1/trips</code> · <code>GET /api/v1/trips/{id}</code> · <code>GET /api/v1/companies</code> · <code>GET /api/v1/currencies</code> · <code>POST /api/v1/bookings</code></p>
                <p>إرسال الحجز ينشئ عملية معلّقة باستخدام الأسعار المسجلة داخل النظام؛ لا تُرسل سعرًا من الموقع.</p>
            </div>
            @if($tenants->isEmpty())
            <div class="empty-note">لا توجد شركات حتى الآن. ابدأ بإضافة شركة جديدة.</div>
        @endif
        <div class="company-list">
        @foreach($tenants as $tenant)
            <div class="company-item">
            <div class="company-item-head">
                <strong>{{ $tenant->name }}</strong>
                <span class="tenant-status {{ $tenant->status ? 'active' : 'suspended' }}">{{ $tenant->status ? 'نشطة' : 'موقوفة' }}</span>
            </div>
            <form method="post" action="{{ route('superadmin.tenants.update', $tenant) }}" class="company-form">
                @csrf
                @method('put')
                <label>اسم الشركة<input name="name" required value="{{ $tenant->name }}"></label>
                <label>البريد الإلكتروني<input name="email" type="email" value="{{ $tenant->email }}"></label>
                <label>الهاتف<input name="phone" value="{{ $tenant->phone }}"></label>
                <label>العنوان<input name="address" value="{{ $tenant->address }}"></label>
                <label>الحالة<select name="status"><option value="1" @selected($tenant->status)>مفعلة</option><option value="0" @selected(!$tenant->status)>موقوفة</option></select></label>
                <div class="company-save"><span class="company-users-count">{{ $tenant->users_count }} مستخدم مرتبط بهذه الشركة</span><button type="submit">حفظ التعديلات</button></div>
            </form>
            <div class="company-form">
                <span>{{ $tenant->api_key_prefix ? 'API مفعّل — '.$tenant->api_key_prefix.'…' : 'لا يوجد مفتاح API' }}</span>
                <a class="tenant-card-link" href="{{ route('superadmin.tenants.apis') }}">إدارة صلاحيات ومفتاح API</a>
            </div>
            </div>
        @endforeach
        </div>
    </section>
    @endif

    @if($page === 'company-apis')
    @php
        $apiActiveKeysCount = $tenants->filter(fn ($tenant) => $tenant->api_key_prefix
            && $tenant->status
            && (! $tenant->api_key_expires_at || ! today()->gt($tenant->api_key_expires_at)))->count();
        $apiExpiredKeysCount = $tenants->filter(fn ($tenant) => $tenant->api_key_prefix
            && $tenant->api_key_expires_at
            && today()->gt($tenant->api_key_expires_at))->count();
        $apiMissingKeysCount = $tenants->filter(fn ($tenant) => ! $tenant->api_key_prefix)->count();
    @endphp
    @if($generatedApiKey)
        <div class="api-success-overlay" id="api-success-overlay">
            <section class="api-success-modal" role="dialog" aria-modal="true" aria-labelledby="api-success-title">
                <button class="api-success-close" type="button" data-close-api-success aria-label="إغلاق">×</button>
                <div class="api-success-icon" aria-hidden="true">✓</div>
                <h2 id="api-success-title">مبروك! تم إنشاء API بنجاح</h2>
                <p>تم تفعيل API لشركة <strong>{{ $generatedApiCompany }}</strong>. انسخ المفتاح الآن واحفظه في مكان آمن.</p>
                <span class="api-success-label">مفتاح API</span>
                <div class="api-success-key-row">
                    <code class="api-success-key" id="generated-api-key">{{ $generatedApiKey }}</code>
                    <button type="button" class="api-copy-button" data-copy-target="generated-api-key" data-copy-message="key" aria-label="نسخ مفتاح API الكامل">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2"></rect><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"></path></svg>
                        نسخ مفتاح API
                    </button>
                </div>
                <span class="api-success-label">رابط API الأساسي</span>
                <div class="api-success-url">
                    <code id="generated-api-url" dir="ltr">{{ url('/api/v1') }}</code>
                    <button type="button" class="api-copy-button" data-copy-text="{{ url('/api/v1') }}" data-copy-message="base-url">نسخ الرابط</button>
                </div>
                <p class="api-success-note">المفتاح الكامل يظهر مرة واحدة فقط. أرسله في ترويسة Authorization بصيغة Bearer، ولا تضعه في كود المتصفح.</p>
            </section>
        </div>
    @endif
    <div class="api-copy-toast" id="api-copy-toast" role="status" aria-live="polite" hidden></div>
    <section class="tenant-card">
        <div class="tenant-card-heading">
            <div><h2>مفاتيح API حسب الشركة</h2><p>تحكم في مفاتيح المواقع وصلاحياتها وتابع حالة الاتصال لكل شركة من مكان واحد.</p></div>
        </div>
        <div class="api-overview" aria-label="ملخص مفاتيح API">
            <article class="api-overview-card is-active">
                <span class="api-overview-icon" aria-hidden="true">✓</span>
                <span class="api-overview-copy"><span>مفاتيح فعالة</span><strong>{{ $apiActiveKeysCount }}</strong></span>
            </article>
            <article class="api-overview-card is-expired">
                <span class="api-overview-icon" aria-hidden="true">!</span>
                <span class="api-overview-copy"><span>مفاتيح منتهية</span><strong>{{ $apiExpiredKeysCount }}</strong></span>
            </article>
            <article class="api-overview-card">
                <span class="api-overview-icon" aria-hidden="true">＋</span>
                <span class="api-overview-copy"><span>بدون مفتاح</span><strong>{{ $apiMissingKeysCount }}</strong></span>
            </article>
        </div>
        <div class="api-management-tools">
            <label class="api-company-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 4.2 4.2"></path></svg>
                <input type="search" id="api-company-filter" placeholder="ابحث باسم الشركة أو حالة المفتاح..." autocomplete="off" aria-label="ابحث عن شركة">
            </label>
            <div class="api-management-links">
                <a class="api-management-link" href="{{ url('/api/documentation') }}" target="_blank" rel="noopener">فتح التوثيق التفاعلي <span aria-hidden="true">↗</span></a>
                <button type="button" class="api-management-link api-copy-button" data-copy-text="{{ url('/api/v1') }}" data-copy-message="base-url">نسخ رابط API الأساسي</button>
            </div>
        </div>
        <p class="api-filter-count" id="api-filter-count" role="status" aria-live="polite">{{ $tenants->count() }} شركة</p>
        @if($tenants->isEmpty())
            <div class="empty-note">لا توجد شركات حتى الآن.</div>
        @else
            <div class="api-company-list" id="api-company-list" aria-live="polite">
                @foreach($tenants as $tenant)
                    <article class="api-company-card" data-api-company-card data-search="{{ mb_strtolower($tenant->name.' '.$tenant->api_key_prefix.' '.$tenant->api_key_status.' '.$tenant->api_key_scopes_label) }}">
                        <div class="api-company-head">
                            <div class="api-company-identity">
                                <span class="api-company-avatar" aria-hidden="true">API</span>
                                <strong>{{ $tenant->name }}</strong>
                            </div>
                            <span class="tenant-status {{ $tenant->status ? 'active' : 'suspended' }}">{{ $tenant->status ? 'نشطة' : 'موقوفة' }}</span>
                        </div>
                        <div class="api-company-content">
                            <p class="api-company-state">
                                <span class="api-key-status {{ $tenant->api_key_status_class }}">{{ $tenant->api_key_status }}</span>
                                <span>{{ $tenant->api_key_expiry_label }}</span>
                                <span>آخر استخدام: {{ $tenant->api_key_last_used_label }}</span>
                            </p>
                            <div class="api-company-keybox">
                                <div class="api-company-key-label">
                                    <span>مفتاح API</span>
                                    <span>{{ $tenant->api_key_prefix ? 'النسخ متاح عند إنشاء المفتاح فقط' : 'المفتاح الكامل يظهر مرة واحدة بعد الإنشاء' }}</span>
                                </div>
                                <div class="api-company-key-row">
                                    <code class="api-company-key-value">{{ $tenant->api_key_prefix ? $tenant->api_key_prefix.'••••••' : '—' }}</code>
                                </div>
                            </div>
                            <form class="api-expiry-form" method="post" action="{{ route('superadmin.tenants.api-key.generate', $tenant) }}" onsubmit="return confirm('{{ $tenant->api_key_prefix ? 'إنشاء مفتاح جديد سيوقف المفتاح الحالي. متابعة؟' : 'إنشاء مفتاح API لهذه الشركة؟' }}')">
                                @csrf
                                <label>تاريخ انتهاء الصلاحية
                                    <input type="date" name="expires_at" min="{{ today()->toDateString() }}" value="{{ $tenant->api_key_expires_at?->format('Y-m-d') ?? today()->addYear()->toDateString() }}" required>
                                </label>
                                <fieldset class="api-scope-options">
                                    <legend>صلاحيات المفتاح</legend>
                                    @foreach($availableApiScopes as $scope)
                                        <label><input type="checkbox" name="scopes[]" value="{{ $scope }}" @checked(in_array($scope, $tenant->api_key_scopes ?? \App\Support\TenantApiScopes::DEFAULT, true))>{{ $scope }}</label>
                                    @endforeach
                                </fieldset>
                                <button type="submit">{{ $tenant->api_key_prefix ? 'تجديد وتحديد الصلاحية' : 'إنشاء API' }}</button>
                            </form>
                            <p class="api-company-scopes">الصلاحيات الحالية: {{ $tenant->api_key_scopes_label }}</p>
                            @if($tenant->api_key_prefix)
                                <details class="api-endpoints-section">
                                    <summary class="api-endpoints-heading"><span>روابط الـ API المتاحة</span><span>{{ count($apiEndpoints) }} واجهات</span></summary>
                                    <div class="api-endpoint-list" aria-label="روابط واجهات API الخاصة بالشركة">
                                        @foreach($apiEndpoints as $endpoint)
                                            <div class="api-endpoint-link">
                                                <span class="api-method {{ strtolower($endpoint['method']) }}">{{ $endpoint['method'] }}</span>
                                                <span class="api-endpoint-meta">
                                                    <strong>{{ $endpoint['label'] }}</strong>
                                                    <code dir="ltr">{{ url('/api/v1'.$endpoint['path']) }}</code>
                                                    <span class="api-endpoint-scope">{{ $endpoint['scope'] }}</span>
                                                </span>
                                                <button type="button" class="api-copy-button" data-copy-text="{{ url('/api/v1'.$endpoint['path']) }}" data-copy-message="endpoint" aria-label="Copy {{ $endpoint['label'] }}">نسخ</button>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endif
                            <div class="api-actions">
                                @if($tenant->api_key_prefix)
                                    <form method="post" action="{{ route('superadmin.tenants.api-key.revoke', $tenant) }}" onsubmit="return confirm('إلغاء مفتاح API سيوقف اتصال الموقع فوراً. متابعة؟')">
                                    @csrf
                                    @method('delete')
                                    <button type="submit">إلغاء المفتاح</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="api-no-results" id="api-company-no-results" hidden>لا توجد شركات مطابقة للبحث.</div>
        @endif
        <div class="api-docs">
            <h3>واجهات الموقع</h3>
            <p>أرسل مفتاح الشركة في ترويسة الطلب: <code dir="ltr">Authorization: Bearer YOUR_API_KEY</code></p>
            <p>عنوان API الأساسي: <code dir="ltr">{{ url('/api/v1') }}</code></p>
            <div class="api-endpoints" dir="ltr">
                <code>GET /trips</code>
                <code>GET /trips/{id}</code>
                <code>GET /companies</code>
                <code>GET /currencies</code>
                <code>POST /bookings</code>
            </div>
            <p>إنشاء الحجز يستخدم الأسعار المسجلة في النظام؛ لا ترسل السعر من الموقع. كل مفتاح يصل لبيانات شركته فقط.</p>
        </div>
    </section>
    @endif

    @if($page === 'users')
    <section class="tenant-card">
        <div class="tenant-card-heading">
            <div><h2>إدارة المستخدمين</h2><p>{{ $users->count() }} مستخدم في القائمة الحالية · تفعيل المستخدم يتم تلقائياً عند إنشائه</p></div>
        </div>
        <form method="get" action="{{ route('superadmin.users.index') }}" class="tenant-filter">
            <label>
                تصفية المستخدمين حسب الشركة
                <select name="tenant_id" class="filter-select">
                    <option value="">كل الشركات</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected((string) $selectedTenantId === (string) $tenant->id)>{{ $tenant->name }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit">تصفية</button>
            @if($selectedTenantId !== '')
                <a href="{{ route('superadmin.users.index') }}">مسح الفلتر</a>
            @endif
        </form>
        <div class="tenant-table-wrap">
            <table class="tenant-table">
                <thead><tr><th>المستخدم</th><th>الشركة</th><th>الدور</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td><span class="user-primary">{{ $user->name }}</span><span class="user-secondary">{{ $user->username }} · {{ $user->email }}</span></td>
                        <td>{{ $user->tenant?->name ?? 'بدون شركة' }}</td>
                        <td>{{ $roleLabels[$user->role] ?? $user->role }}</td>
                        <td><span class="tenant-status {{ $user->status ? 'active' : 'suspended' }}">{{ $user->status ? 'نشط' : 'موقوف' }}</span></td>
                        <td>
                            <details class="user-edit">
                                <summary>تعديل البيانات</summary>
                                <form method="post" action="{{ route('superadmin.users.update', $user->id) }}" class="user-edit-form">
                                    @csrf
                                    @method('put')
                                    <input name="name" aria-label="اسم المستخدم" required value="{{ $user->name }}">
                                    <input name="username" aria-label="اسم الدخول" required value="{{ $user->username }}">
                                    <input name="email" aria-label="البريد الإلكتروني" type="email" required autocomplete="username" value="{{ $user->email }}">
                                    <select name="tenant_id" aria-label="الشركة" required>
                                        @foreach($tenants->where('status', true) as $tenant)
                                            <option value="{{ $tenant->id }}" @selected($user->tenant_id === $tenant->id)>{{ $tenant->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="role" aria-label="دور المستخدم" required>
                                        @foreach($roleLabels as $role => $label)
                                            <option value="{{ $role }}" @selected($user->role === $role)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select name="status" aria-label="حالة المستخدم" required>
                                        <option value="1" @selected($user->status)>نشط</option>
                                        <option value="0" @selected(!$user->status)>موقوف</option>
                                    </select>
                                    <input name="password" aria-label="كلمة مرور جديدة اختيارية" type="password" minlength="8" placeholder="كلمة مرور جديدة (اختياري)" autocomplete="new-password">
                                    <button type="submit">حفظ المستخدم</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @endforeach
                @if($users->isEmpty())
                    <tr><td colspan="5"><div class="empty-note">لا يوجد مستخدمون مطابقون لهذا الفلتر.</div></td></tr>
                @endif
                </tbody>
            </table>
        </div>
    </section>
    @endif
</main>
@endsection

@section('scripts')
    <script>
        (() => {
            const toast = document.getElementById('api-copy-toast');
            let toastTimer;
            const successMessages = {
                key: @json(__('تم نسخ المفتاح. احفظه في مكان آمن.')),
                'base-url': @json(__('تم نسخ رابط API الأساسي.')),
                endpoint: @json(__('تم نسخ رابط الواجهة بنجاح.'))
            };
            const companyFilter = document.getElementById('api-company-filter');
            const companyCards = [...document.querySelectorAll('[data-api-company-card]')];
            const noCompanyResults = document.getElementById('api-company-no-results');
            const filterCount = document.getElementById('api-filter-count');
            document.querySelectorAll('[data-permission-target]').forEach(button => {
                button.addEventListener('click', () => {
                    const target = document.getElementById(button.dataset.permissionTarget);
                    if (!target) return;

                    const expanded = target.hidden;
                    target.hidden = !expanded;
                    button.setAttribute('aria-expanded', String(expanded));
                    button.textContent = expanded ? button.dataset.showLess : button.dataset.showAll;
                });
            });
            const updateCompanyFilter = () => {
                if (!companyFilter) return;
                const query = companyFilter.value.trim().toLocaleLowerCase();
                let visibleCount = 0;
                companyCards.forEach(card => {
                    const matches = (card.dataset.search || '').toLocaleLowerCase().includes(query);
                    card.hidden = !matches;
                    if (matches) visibleCount++;
                });
                if (noCompanyResults) noCompanyResults.hidden = visibleCount > 0;
                if (filterCount) {
                    filterCount.textContent = query
                        ? `${visibleCount} نتيجة من ${companyCards.length} شركة`
                        : `${companyCards.length} شركة`;
                }
            };
            companyFilter?.addEventListener('input', updateCompanyFilter);

            const copyText = async text => {
                if (navigator.clipboard && window.isSecureContext) {
                    try {
                        await navigator.clipboard.writeText(text);
                        return;
                    } catch {
                        // Continue with the legacy clipboard path when browser permission is denied.
                    }
                }

                const input = document.createElement('textarea');
                input.value = text;
                input.setAttribute('readonly', '');
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.append(input);
                input.select();
                const copied = document.execCommand('copy');
                input.remove();
                if (!copied) throw new Error('Clipboard copy was denied.');
            };

            document.querySelectorAll('[data-copy-text], [data-copy-target]').forEach(button => {
                button.addEventListener('click', async () => {
                    try {
                        const target = button.dataset.copyTarget
                            ? document.getElementById(button.dataset.copyTarget)
                            : null;
                        const text = target ? target.textContent.trim() : button.dataset.copyText;
                        if (!text) throw new Error('Nothing to copy.');
                        await copyText(text);
                        if (toast) {
                            toast.textContent = successMessages[button.dataset.copyMessage] || @json(__('تم النسخ بنجاح.'));
                            toast.hidden = false;
                            window.clearTimeout(toastTimer);
                            toastTimer = window.setTimeout(() => { toast.hidden = true; }, 2800);
                        }
                    } catch {
                        const target = button.dataset.copyTarget
                            ? document.getElementById(button.dataset.copyTarget)
                            : null;
                        if (target) {
                            const selection = window.getSelection();
                            const range = document.createRange();
                            range.selectNodeContents(target);
                            selection?.removeAllRanges();
                            selection?.addRange(range);
                        }
                        if (toast) {
                            toast.textContent = target
                                ? 'تعذر النسخ تلقائيًا. تم تحديد المفتاح؛ اضغط Ctrl+C لنسخه.'
                                : 'تعذر النسخ تلقائيًا. حاول مرة أخرى أو انسخ الرابط يدويًا.';
                            toast.hidden = false;
                        }
                    }
                });
            });

            const overlay = document.getElementById('api-success-overlay');
            const closeButton = document.querySelector('[data-close-api-success]');
            const closeModal = () => {
                if (overlay) overlay.remove();
            };
            closeButton?.addEventListener('click', closeModal);
            overlay?.addEventListener('click', event => {
                if (event.target === overlay) closeModal();
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') closeModal();
            });
        })();
    </script>
@endsection
