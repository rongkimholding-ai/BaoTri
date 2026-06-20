<x-guest-layout>

<style>
    body{
        background:#f4f6f9;
    }

    .login-wrapper{
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:30px;
    }

    .login-card{
        width:100%;
        max-width:1200px;
        background:#fff;
        border-radius:24px;
        overflow:hidden;
        box-shadow:0 20px 50px rgba(0,0,0,.08);
    }

    .maintenance-side{
        background:#faf6ef;
        display:flex;
        flex-direction:column;
        justify-content:center;
        align-items:center;
        padding:50px;
    }

    .maintenance-side img{
        max-width:100%;
        height:auto;
    }

    .form-side{
        padding:70px;
        display:flex;
        align-items:center;
    }

    .form-content{
        width:100%;
        max-width:420px;
        margin:auto;
    }

    .system-title{
        font-size:54px;
        font-weight:700;
        line-height:1.1;
        color:#13294b;
    }

    .system-title span{
        color:#e67e22;
        display:block;
    }

    .title-line{
        width:70px;
        height:4px;
        background:#e67e22;
        margin:24px 0;
        border-radius:5px;
    }

    .description{
        color:#64748b;
        font-size:18px;
        margin-bottom:35px;
    }

    .input-label{
        display:block;
        margin-bottom:8px;
        font-weight:600;
        color:#1e293b;
    }

    .form-input{
        width:100%;
        border:1px solid #dbe2ea;
        border-radius:12px;
        padding:14px 16px;
        margin-bottom:20px;
        font-size:15px;
    }

    .form-input:focus{
        outline:none;
        border-color:#e67e22;
        box-shadow:0 0 0 3px rgba(230,126,34,.15);
    }

    .btn-login{
        width:100%;
        background:#e67e22;
        color:#fff;
        border:none;
        border-radius:12px;
        padding:15px;
        font-weight:700;
        font-size:16px;
        transition:.3s;
    }

    .btn-login:hover{
        background:#d56d12;
    }

    .register-link{
        text-align:center;
        margin-top:20px;
    }

    .register-link a{
        color:#e67e22;
        font-weight:600;
        text-decoration:none;
    }

    .feature-box{
        margin-top:30px;
        text-align:center;
        color:#475569;
    }

    @media(max-width:992px){

        .maintenance-side{
            display:none;
        }

        .form-side{
            padding:40px 25px;
        }

        .system-title{
            font-size:38px;
        }
    }
</style>

<div class="login-wrapper">

    <div class="login-card">

        <div class="row g-0">

            <div class="col-lg-6 maintenance-side">

                <img
                    src="{{ asset('images/maintenance.jpg') }}"
                    alt="Maintenance"
                >

                <div class="feature-box">
                    <h5>Hệ thống quản lý bảo trì</h5>
                    <small>Ổn định • Hiệu quả • Nhanh chóng</small>
                </div>

            </div>

            <div class="col-lg-6 form-side">

                <div class="form-content">

                    <h1 class="system-title">
                        Hệ thống
                        <span>bảo trì</span>
                    </h1>

                    <div class="title-line"></div>

                    <p class="description">
                        Quản lý yêu cầu bảo trì và theo dõi tiến độ xử lý.
                    </p>

                    <form method="POST" action="{{ route('login') }}" autocomplete="on">
                        @csrf

                        <label class="input-label">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-input"
                            autocomplete="username"
                            required
                            autofocus
                        >

                        <label class="input-label">
                            Mật khẩu
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-input"
                            autocomplete="current-password"
                            required
                        >

                        <div class="mb-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    class="rounded border-gray-300"
                                >
                                <span>Ghi nhớ đăng nhập</span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            class="btn-login"
                        >
                            ĐĂNG NHẬP
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</x-guest-layout>
