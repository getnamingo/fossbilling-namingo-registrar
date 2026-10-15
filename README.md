# Namingo Registrar for FOSSBilling
FOSSBilling modules for Namingo Registrar, implementing the technical requirements for ICANN-accredited registrars.

> [!IMPORTANT]
> This repository is part of [Namingo Registrar](https://github.com/getnamingo/registrar) and is intended to be used exclusively with it. It is not designed for standalone use.

[![StandWithUkraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/badges/StandWithUkraine.svg)](https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/README.md)

[![SWUbanner](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/README.md)

## Installation

```bash
TMP=$(mktemp -d)
git clone --depth 1 https://github.com/getnamingo/fossbilling-namingo-registrar.git "$TMP/repo"
cp -a "$TMP/repo"/{Registrar,Domaincontactvalidation,Validation,Contact,Tmch,Whois,Mosapimonitor} /var/www/modules/
cp -a "$TMP/repo/check.php" /var/www/
rm -rf "$TMP"
```

## Upgrade

```bash
TMP=$(mktemp -d)
git clone --depth 1 https://github.com/getnamingo/fossbilling-namingo-registrar.git "$TMP/repo"
for module in Registrar Domaincontactvalidation Validation Contact Tmch Whois Mosapimonitor; do
    cp -a "$TMP/repo/$module/." "/var/www/modules/$module/"
done
rm -rf "$TMP"

# Clear FOSSBilling cache and reload PHP-FPM
rm -rf /var/www/data/cache/*
systemctl reload php8.3-fpm
```

## Support This Project

If you find Namingo Registrar for FOSSBilling useful, consider donating:

- [Donate via Stripe](https://donate.stripe.com/7sI2aI4jV3Offn28ww)
- BTC: `bc1q9jhxjlnzv0x4wzxfp8xzc6w289ewggtds54uqa`
- ETH: `0x330c1b148368EE4B8756B176f1766d52132f0Ea8`

## Licensing

Namingo Registrar for FOSSBilling is licensed under the Apache License 2.0.