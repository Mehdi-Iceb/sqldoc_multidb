<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .info-box {
            background: white;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .info-box strong {
            color: #667eea;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 12px;
        }
        .trial-badge {
            background: #10b981;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            display: inline-block;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Welcome to {{ config('app.name') }} !</h1>
        <p>You have successfully created your space</p>
    </div>

    <div class="content">
        <p>Hello <strong>{{ $tenant->contact_name }}</strong>,</p>

        <p>Congratulations! Your space <strong>{{ $tenant->name }}</strong> has been created successfully.</p>

        <div class="info-box">
            <h3>🌐 log in information</h3>
            <p><strong>Your space URL :</strong><br>
            <a href="{{ $tenantUrl }}">{{ $tenantUrl }}</a></p>
            
            <p><strong>Email :</strong> {{ $tenant->contact_email }}</p>
            
            <p style="color: #dc2626; font-size: 12px;">
                ⚠️ For security reasons, we recommend that you change your password after your first login.
            </p>
        </div>

        <div class="info-box">
            <h3>📦 Your subscriptiont</h3>
            <p><strong>Plan :</strong> {{ $subscription->subscriptionPlan->name }}</p>
            <p><strong>Billing :</strong> {{ $subscription->billing_cycle === 'yearly' ? 'Annuelle' : 'Mensuelle' }}</p>
            
            @if($subscription->isOnTrial())
                <span class="trial-badge">🎁 3 months free trial</span>
                <p>Your trial period ends on <strong>{{ $subscription->trial_ends_at->format('d/m/Y') }}</strong></p>
                <p style="font-size: 14px; color: #666;">
                    Enjoy all features without limits for 3 months. 
                    No bank card required during this period.
                </p>
            @endif
            
            @if($subscription->subscriptionPlan->max_employees)
                <p><strong>Maximum users:</strong> {{ $subscription->subscriptionPlan->max_employees }}</p>
            @else
                <p><strong>Users :</strong> Unlimited</p>
            @endif
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $tenantUrl }}" class="button">
                Log in now →
            </a>
        </div>

        <div class="info-box">
            <h3>Next steps</h3>
            <ol>
                <li>Log in to your space</li>
                <li>Invite your colleagues</li>
                <li>Start using {{ config('app.name') }}</li>
            </ol>
        </div>

        <p>If you have any questions, please do not hesitate to contact us at <a href="mailto:{{ config('mail.support_email', 'support@' . config('app.domain')) }}">{{ config('mail.support_email', 'support@' . config('app.domain')) }}</a></p>

        <p>Sincerely,<br>
        Team {{ config('app.name') }}</p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p>You are receiving this email because you created an account on {{ config('app.name') }}.</p>
    </div>
</body>
</html>