magento-openid
==============

Adds OpenID authentication to Magento.

Installation
------------

Copy the files to your Magento DocumentRoot. Clear caches if necessary.


Configuration
-------------

Add the user-identity mapping to your local.xml.

```
<default>
    <Dachcom_OpenID>
        <users>
            <admin>http://admin.openid-provider.com/</admin>
        </users>
    </Dachcom_OpenID>
</default>
```

Planned features
----------------

* user-identity mapping in backend.
