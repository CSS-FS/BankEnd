@extends('emails.layouts.base')

@section('content')
    <h2 style="margin:0 0 10px;font-size:20px;">Welcome to {{ config('flocksense.brand_name') }}</h2>

    <p style="margin:0 0 14px;line-height:1.7;color:#334155;">
        Hi <strong>{{ $user->name }}</strong>,
    </p>

    <p style="margin:0 0 14px;line-height:1.7;color:#334155;">
        Your account has been created on <strong>{{ config('flocksense.brand_name') }}</strong>.
        Below are your login credentials:
    </p>

    <div style="margin:18px 0;padding:14px 16px;background:#ecfeff;border:1px solid #cffafe;border-radius:12px;color:#155e75;">
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="padding:6px 0;font-weight:600;">Email:</td>
                <td style="padding:6px 0;">{{ $user->email }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0;font-weight:600;">Password:</td>
                <td style="padding:6px 0;">{{ $plainPassword }}</td>
            </tr>
        </table>
    </div>

    <p style="margin:0 0 14px;line-height:1.7;color:#dc2626;font-weight:600;">
        For security reasons, you will be required to change your password on first login.
    </p>

    <div style="margin:18px 0;text-align:center;">
        <a href="{{ $loginUrl }}" style="display:inline-block;padding:12px 28px;background:#0b3b3b;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;">
            Login to Your Account
        </a>
    </div>

    <p style="margin:0;line-height:1.7;color:#334155;">
        Thanks,<br>
        <strong>{{ config('flocksense.brand_name') }} Team</strong>
    </p>
@endsection
