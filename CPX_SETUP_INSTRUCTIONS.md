# 🎯 CPX Research Setup Instructions

## Step 1: Add Secret Key to Config

1. Go to your CPX Research dashboard
2. Find your **Secure Hash** or **Secret Key**
3. Edit `/httpdocs/app/config.php`:

```php
'cpxresearch' => [
    'enabled' => true,
    'app_id' => '29798',
    'secret_key' => 'YOUR_SECRET_KEY_HERE', // ← Paste your secret key here!
    'survey_url' => 'https://offers.cpx-research.com/index.php',
],
```

---

## Step 2: Configure CPX Research Dashboard

### Main Postback URL

In your CPX Research dashboard, go to: **Edit App → Postback Settings**

Enter this URL in the **Main Postback URL** field:

```
https://enderbit.com/app/cpxresearch_callback.php?status={status}&trans_id={trans_id}&user_id={user_id}&subid_1={subid_1}&subid_2={subid_2}&amount_local={amount_local}&amount_usd={amount_usd}&type={type}&secure_hash={secure_hash}
```

### Parameter Explanation:
- `{status}` - 1 = completed, 2 = canceled
- `{trans_id}` - Unique transaction ID
- `{user_id}` - Your user's email (ext_user_id)
- `{subid_1}` - Optional tracking parameter
- `{subid_2}` - Optional tracking parameter
- `{amount_local}` - Amount in your currency
- `{amount_usd}` - Amount in USD
- `{type}` - "out", "complete", or "bonus"
- `{secure_hash}` - MD5 hash for validation

---

## Step 3: Whitelist CPX Research IPs (Optional but Recommended)

CPX Research sends callbacks from these IPs:
- `188.40.3.73`
- `2a01:4f8:d0a:30ff::2`
- `157.90.97.92`

### To Enable IP Checking:

Edit `/httpdocs/app/cpxresearch_callback.php` and uncomment these lines (around line 45):

```php
// Optional: Check if request is from CPX Research IP (uncomment to enable)
require_once __DIR__ . '/cpxresearch.php';
if (!CPXResearch::isValidCPXIP()) {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Invalid IP: $clientIP\n", FILE_APPEND);
    echo "error: invalid ip";
    exit;
}
```

Remove the `//` at the beginning of lines 39-43 to enable IP checking.

---

## Step 4: Test the Integration

### 4.1 Test the Survey Wall

1. Log into your EnderBit account
2. Go to: https://enderbit.com/app/earn_credits.php
3. You should see the CPX Research survey wall load
4. Try completing a test survey

### 4.2 Check Callback Logs

Monitor the callback log file:

```bash
tail -f /var/www/vhosts/jasonzli.fun/enderbit.com/httpdocs/app/cpxresearch_callbacks.log
```

You should see entries like:
```
[2025-11-01 12:34:56] Callback received from IP: 188.40.3.73 - Raw params: {"status":"1","trans_id":"ABC123",...}
[2025-11-01 12:34:56] SUCCESS: Reward processed - User: user@email.com | Amount: 0.5 | TransID: ABC123 | Type: complete
```

### 4.3 Verify Credits

1. Complete a survey
2. Check user's credit balance
3. Credits should be added automatically within minutes

---

## Step 5: Adjust Credit Conversion Rate (Optional)

The default conversion in `/httpdocs/app/cpxresearch.php` is:

```php
$credits = round($amount * 10); // 1 unit = 10 credits
```

**Examples with current rate:**
- Survey pays $0.50 → User gets 5 credits
- Survey pays $1.00 → User gets 10 credits
- Survey pays $2.50 → User gets 25 credits

**To change the rate:**

Edit the `processReward()` method in `/httpdocs/app/cpxresearch.php`:

```php
// For 1:1 conversion (1 unit = 1 credit):
$credits = round($amount);

// For 1:20 conversion (1 unit = 20 credits):
$credits = round($amount * 20);

// For 1:100 conversion (1 unit = 100 credits):
$credits = round($amount * 100);
```

---

## Troubleshooting

### Survey wall not loading?
- Check that secret key is added to config.php
- Check browser console for errors
- Verify app_id is correct (29798)

### Callbacks not working?
- Check `/httpdocs/app/cpxresearch_callbacks.log`
- Verify postback URL is correct in CPX dashboard
- Make sure the callback URL is accessible (test it directly)
- Check if hash validation is failing (secret key mismatch)

### Credits not being added?
- Check `/httpdocs/app/cpxresearch_rewards.log`
- Verify user email exists in system
- Check credit conversion rate
- Look for errors in callback log

### Status 2 (Canceled) callbacks?
CPX will send status=2 if fraud is detected (usually 15-60 days later). Currently, the system just logs these but doesn't reverse credits. You may want to implement credit reversal for fraud cases.

---

## Important Notes

1. **Secret Key Security**: Never commit your secret key to GitHub. Keep it only in `config.php` which is in `.gitignore`.

2. **Fraud Detection**: CPX may call your postback again with `status=2` if fraud is detected. Consider implementing credit reversal for these cases.

3. **Transaction IDs**: CPX sends unique transaction IDs. You could add duplicate detection to prevent processing the same transaction twice.

4. **IP Whitelist**: The IP check is optional but recommended for extra security.

5. **Logs**: Monitor both log files regularly:
   - `cpxresearch_callbacks.log` - All incoming callbacks
   - `cpxresearch_rewards.log` - Processed rewards

---

## Summary Checklist

- [ ] Add secret key to `config.php`
- [ ] Set postback URL in CPX dashboard
- [ ] (Optional) Enable IP whitelist checking
- [ ] Test survey wall loads
- [ ] Complete a test survey
- [ ] Verify credits are added
- [ ] Adjust conversion rate if needed
- [ ] Monitor logs for issues

---

*Your CPX Research integration is now complete!* 🎉
