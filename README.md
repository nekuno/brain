Nekuno Brain2
===================

After cloning execute:

`cd brain2`

`composer install`

Then, generate the SSH keys for LexikJWTAuthenticationBundle (https://github.com/lexik/LexikJWTAuthenticationBundle)

`mkdir config/jwt`

`openssl genrsa -out config/jwt/private.pem -aes256 4096`

`openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem`

In case first openssl command forces you to input password use following to get the private key decrypted

`openssl rsa -in config/jwt/private.pem -out config/jwt/private2.pem`

`mv config/jwt/private.pem config/jwt/private.pem-back`

`mv config/jwt/private2.pem config/jwt/private.pem`