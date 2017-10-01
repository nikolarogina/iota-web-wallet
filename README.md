IOTA Web Wallet
===================

[![N|Solid](https://www.wallet.iota.hr/img/logo-black-small.png)](https://www.wallet.iota.hr)

This is a repository for https://www.wallet.iota.hr.

About the project
-------------

This project is created for several reasons:
- Currently, users of IOTA cryptocurrency have only the [official desktop wallet](https://github.com/iotaledger/wallet/releases) at their disposal, [Android](https://github.com/iotaledger/android-wallet-app) and [iOS](https://iota.tools/wallet) wallets are currently in Beta development. Therefore, an IOTA user cannot access his wallet through smart devices.

- IOTA uses SEED as login key. For many new users this is confusing since everyone is used to standard Email/Password login access. Thats why that type of login has been implemented into this wallet, enabeling easy access along with removing "problems" with SEED login but still keeping security of user account. But of course, loging in with SEED alone is still kept as an option. It's up to user to decide.

- By enabeling easier access with user-friendly interface this is a big step towards bigger adoption for many (especially new) users.

How it works?
-------------

When accessing the wallet, you can choose to login with email and password, or with SEED:

1. To login with email and password you need to register. While registering, you can associate your own existing SEED or create new one. Then you will be asked for PIN. PIN is a 4 digit number that is used as a key for encrypting SEED (using [cryptoJS](https://code.google.com/archive/p/crypto-js/)). SEED is then encrypted with that PIN, and then it's sent to server - **NOTE: PIN or SEED are NOT sent to server, only the AES-256 encrypted form of SEED.**
When preforming any important action in IOTA Web Wallet (updating balance, sending money, generating addresses...) user is asked for this PIN number - without it Crypto.JS cannot decrypt the SEED. 
**Encrypted SEED is sent to server, but before it's saved in user DB, it's aditionally encrypted using a 64-character key**

2. If a user decides to login with his SEED (or he can easily generate a new one) then he is prompted to write a 4-digit PIN. Again, this is just for security reasons, because this encrypted SEED is then saved in [Browser Session Storage](https://www.w3schools.com/html/html5_webstorage.asp).


**Why is encrypted again when registering?**

To brut-force a 4 digit PIN (10,000 combinations) a average computer needs < 1 second. That's why if someone manages to steal user DB, he would still need to [brut-force](https://www.password-depot.com/know-how/brute-force-attacks.htm) a key with **94^64 combinations** decrypt a SEED.


**What about the DB admin, he can decrpyt DB using server-side key and brut-force SEEDs?**

Thats why when you login, you can change your PIN to a standard password ("advanced PIN") - 8-32 characters = **min. 94^8 combinations**.

Then, not even the DB admin with a supercomputer can brute-force your SEED.

**NOTE: THAT ALSO MEANS THAT IF YOU FORGET YOUR PIN YOU CANNOT ACCESS YOU ACCOUNT!**


When you login, you can generate ([browser-side](https://github.com/MrRio/jsPDF)) a .PDF containing your SEED. With it, you can login to other wallets and in case you forget your PIN, you can still access your funds.

Features:
-------------
- Login with SEED / Email + new SEED generator
- Scan QR code containing SEED to login
- Refresh account data
- Transactions (tx) display (sent/received) with linking to [explorer](https://thetangle.org)
- "Auto-monitor" for transactions (check for new/confirmed tx every 3 minutes)
- re-attaching option for pending sent transactions
- Convert IOTA balance / tx value into USD
- Latest receiving address display - text + QR code
- Send IOTA: Scan QR code of address, switch between units (iota, Ki, Mi, Gi, Ti, Pi)
- Double spend warning
- PDF generator with account SEED = "Paper Wallet"
- Change email / password / PIN / delete account
- Contact support (tickets)
- Current IOT/USD price + chart (last month)
- Average Pow display time (footer)
- Node status display (footer)
- WebGL2 browser check

Technical:
-------------
It's written in PHP using [IOTA JS API](https://github.com/iotaledger/iota.lib.js/).


This Web Wallet uses [curl.lib.js](https://github.com/iotaledger/curl.lib.js) to do local Proof of Work (PoW) (**WebGL2 browser is required!**). 


Acknowledgements:
-------------
Web wallet was inspired by [danilind web wallet](https://www.reddit.com/r/Iota/comments/6jld9z/iota_web_wallet/). Special thanks to [pRizz](https://github.com/pRizz) for help with new curl implemmentation and developers on [Slack](slack.iota.org), especially Frode Halvorsen (@frha).


Author: dr. Nikola Rogina, admin of local IOTA website for Croatia [iota.hr](https://www.iota.hr/) 

Donations:
-------------
IOTA: UCMXZDZYUDBOFQOYTIRLTMBXAHKOWAUJAYXJTFMZSILBRWTOHSTWNEIJF9P9ZGKLIDYYRKHOXPAESPKECTEXUNUHHW
BTC: 1EdSShMGQKdppzvZeEQougAKbuxeJXLPt3
ETH: 0x0CAd7d76AB6623c3bfe995A244ce5d3C7Bb75A0D
