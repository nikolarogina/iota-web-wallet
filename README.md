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

2. If a user decides to login with his SEED (or he can easily generate a new one) then he is promped to write a 4-digit PIN. Agin, this is just for security reasons, because this encrypted SEED is then saved in [Browser Session Storage](https://www.w3schools.com/html/html5_webstorage.asp)

Why is encrypted again?

To burt-force a 4 digit PIN (10,000 combinations) a averge computer needs < 1 second. That's why if someone manages to steal user DB, he would still need to [brut-force](https://www.password-depot.com/know-how/brute-force-attacks.htm) a key with **94^64 combinations** decrypt a SEED.
