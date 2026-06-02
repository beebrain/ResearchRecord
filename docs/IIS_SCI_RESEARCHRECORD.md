# IIS: sci.uru.ac.th/ResearchRecord

Deploy ResearchRecord as an **IIS Application** on the sci site. Code lives at **`C:\inetpub\ResearchRecord`** (sibling of `C:\inetpub\newscience`).

## URL target

```
https://sci.uru.ac.th/ResearchRecord/
https://sci.uru.ac.th/ResearchRecord/index.php/dashboard
```

IIS Application physical path: **`C:\inetpub\ResearchRecord\public`**

---

## Layout on win-kc

```
C:\inetpub\
  newscience\              ← site sci.uru.ac.th → public\
  ResearchRecord\          ← full CI4 project (separate repo)
    public\                ← IIS Application "ResearchRecord"
    app\, writable\, .env
```

Deploy guide: [SERVER_DEPLOY.md](./SERVER_DEPLOY.md)

---

## IIS resolution for `/ResearchRecord/`

1. **Child Application** alias `ResearchRecord` → `C:\inetpub\ResearchRecord\public` (correct)
2. **Folder** `{newscience-public}\ResearchRecord\` — only matters if no Application exists (avoid stale stub)

| Situation | Result |
|-----------|--------|
| Stub folder `newscience\public\ResearchRecord\` without Application | Wrong files / 404 — remove or rename |
| Application → `C:\inetpub\ResearchRecord\public` | RR app (correct) |

**Check on win-kc:**

```powershell
Import-Module WebAdministration
$siteName = "sci.uru.ac.th"
$root = (Get-Website -Name $siteName).physicalPath
Write-Host "Site root: $root"
Test-Path "C:\inetpub\ResearchRecord\public\index.php"
Get-WebApplication -Site $siteName | Format-Table Path, PhysicalPath -AutoSize
```

---

## Create IIS Application

1. IIS Manager → site **sci.uru.ac.th**
2. **Add Application**
   - **Alias:** `ResearchRecord`
   - **Physical path:** `C:\inetpub\ResearchRecord\public`
3. App pool: No Managed Code, PHP 8.3+ FastCGI (same as newScience)
4. Write access on `C:\inetpub\ResearchRecord\writable\`

---

## Parent site rewrite (optional)

If `C:\inetpub\newscience\public\web.config` catch-all breaks the child app:

```xml
<rule name="Skip ResearchRecord app" stopProcessing="true">
  <match url="^ResearchRecord(/.*)?$" ignoreCase="true" />
  <action type="None" />
</rule>
```

---

## `.env` on server

```powershell
cd C:\inetpub\ResearchRecord
copy .env.production.example .env
```

```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://sci.uru.ac.th/ResearchRecord/'
newscience.baseUrl = "https://sci.uru.ac.th"
newscience_sso.enabled = true
newscience_sso.sharedSecret = "pisit_secret"
uruoauth.enabled = false
```

---

## Verify

```powershell
curl -I https://sci.uru.ac.th/ResearchRecord/
curl -I https://sci.uru.ac.th/ResearchRecord/index.php
```

Expect **200** or **302**, not **404**.

---

## Status

- `https://sci.uru.ac.th/` → **200**
- `https://sci.uru.ac.th/ResearchRecord/` → **404** until Application is configured
