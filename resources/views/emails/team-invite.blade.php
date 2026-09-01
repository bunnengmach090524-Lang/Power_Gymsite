<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; background:#f8fafc; padding:32px;">
  <div style="max-width:480px;margin:0 auto;background:#fff;border-radius:12px;padding:32px;">
    <h2 style="color:#10b981;">អ្នកត្រូវបានអញ្ជើញ!</h2>
    <p><strong>{{ $inviterName }}</strong> បានអញ្ជើញអ្នកចូលរួម <strong>{{ $tenantName }}</strong> ជា {{ $invitedUser->role === 'gym_admin' ? 'Administrator' : 'Staff' }}។</p>
    <p style="margin:24px 0;">
      <a href="{{ $acceptUrl }}" style="background:#10b981;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;">
        កំណត់ Password ហើយចូលប្រើ
      </a>
    </p>
    <p style="color:#94a3b8;font-size:13px;">Link នេះផុតកំណត់ក្នុងរយៈពេល 7 ថ្ងៃ។ បើអ្នកមិនស្គាល់ការអញ្ជើញនេះ សូមមិនអើពើ email នេះ។</p>
  </div>
</body>
</html>