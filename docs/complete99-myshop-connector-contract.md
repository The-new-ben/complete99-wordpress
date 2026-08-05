# חוזה תשתית v2 למחבר Complete99, POS ו-MyShop

סטטוס: תשתית POS ניטרלית ממומשת ב-Complete99; חיבור הספק MyShop חסום במצב `contract_required` ו-`unbound`
סיווג: תפעול פרטי, חוזה אינטגרציה ותכן מתאם עתידי
סכמת מרשם: `complete99-culinary-commerce-registry/v2`
סכמת בקשה: `complete99-pos-catalog-request/v1`
סכמת תשובה: `complete99-pos-catalog-response/v1`
תאריך בדיקה: 2026-08-06
מערכות יעד: WordPress, WooCommerce, מתאם POS, MyShop, קיוסק מגע, קופה ומסך מטבח

## 1. החלטת הארכיטקטורה

WordPress ו-WooCommerce הם מקור האמת היחיד של Complete99.

במצב היעד MyShop היא הקרנה תפעולית של קטלוג, תפריט וזמינות, וכן מקור אירוע להזמנות שנוצרו בקיוסק או בקופה שלה. MyShop אינה בסיס נתונים ראשי נוסף ואינה מנהלת עותק עצמאי שניתן לערוך ללא החזרה מבוקרת ל-WooCommerce.

ב-v2 הקיים כבר ממומש ב-WordPress ממשק קריאה חתום וניטרלי לספק, שמקרין ללקוח POS רק הצעות ערוץ פעילות לאחר התאמה חיה ל-WooCommerce. טרם ממומשים קישור transport לספק MyShop, כתיבה אל endpoint של MyShop, קליטת הזמנות MyShop או סנכרון סטטוסים שלה. המסמך מבחין במפורש בין מה שקיים בקוד לבין תכן המתאם העתידי.

החלוקה המחייבת היא:

| תחום | מקור אמת | מצב v2 | תפקיד MyShop במצב היעד |
| --- | --- | --- | --- |
| מזהה מוצר ו-SKU | WooCommerce | מוקרן מקריאה חיה לפי `woo_product_code` | שמירת המזהה החיצוני ללא שינוי |
| שם, תמונה ושפה | WordPress/WooCommerce והמרשם המאושר | מוקרן לפי locale והצעת ערוץ | הצגה בקיוסק, באפליקציה ובמסכי התפריט |
| קטגוריה ותת-קטגוריה | WordPress/WooCommerce והצעת הערוץ | שתי רמות ב-`kiosk_projection` | שמירת המבנה וסדר התצוגה לאחר אימות חוזה הספק |
| מחיר, מטבע ומס | WooCommerce והצעת ערוץ מאושרת | המחיר והמטבע נבדקים מול WooCommerce בזמן הבקשה | הצגת ההקרנה המאושרת בלבד |
| מלאי וכשירות למכירה | WooCommerce | `availability`, `stock_status` ו-`stock_quantity` נקראים בזמן אמת | הצגת המצב שהתקבל מ-Complete99 |
| הזמנה שנוצרה ב-MyShop | MyShop כמקור האירוע, WooCommerce כרשומה הקנונית | תכן עתידי, לא ממומש ב-v2 | שליחת אירוע מלא וקבלת אישור קליטה |
| סטטוס הכנה בקופה או ב-KDS | MyShop כמקור התצפית, WooCommerce כרשומה הקנונית | תכן עתידי, לא ממומש ב-v2 | שליחת מעבר סטטוס וקבלת סטטוס מאושר |
| תשלום | ספק הסליקה כמקור התוצאה, WooCommerce כרשומת ההתאמה | תכן עתידי, לא ממומש ב-v2 | העברת אסמכתה אטומה וסטטוס בלבד |

שינוי קטלוג ידני בתוך MyShop חייב להיות חסום, או להיחשב בקשת שינוי שאינה נכנסת לתוקף עד ש-WooCommerce מאשר ומקרין אותה מחדש. אין לבצע עריכה כפולה בשתי המערכות.

## 2. גבול החוזה

### המשטח הממומש כעת

המשטח הממומש ב-v2 הוא בקשת `POST` לקריאת הקרנת קטלוג POS:

`/wp-json/complete99/v1/integrations/pos/catalog`

זהו endpoint של Complete99 בלבד. הוא אינו endpoint של MyShop ואינו מניח payload, כתובת, webhook או credential של MyShop. הנתיב שנכנס למחרוזת החתימה הוא נתיב WordPress REST הפנימי:

`/complete99/v1/integrations/pos/catalog`

המשטח מחזיר קטלוג לקריאה בלבד. אין בו כתיבת קטלוג לספק, קליטת הזמנה, שינוי מלאי, שינוי סטטוס או סליקה.

### התכן שעדיין ממתין לחוזה MyShop

המסמך שומר גם חמישה שמות לוגיים ניטרליים לתכן המתאם העתידי. הם אינם routes קיימים, אינם payloads מאומתים של MyShop ואינם התחייבות לכך שהספק תומך בהם:

1. `catalog.item.upsert`
2. `availability.set`
3. `order.created`
4. `order.status.changed`
5. `sync.receipt`

אם MyShop משתמשת בשמות או במבנים אחרים, שכבת המתאם תמפה ביניהם לבין המודל הקנוני בלי לשנות את WordPress ו-WooCommerce כמקור האמת.

## 3. כיוון הזרימה ומכונת המצבים

### זרימת v2 הממומשת

1. צרכן POS שולח גוף JSON מדויק ל-endpoint הניטרלי של Complete99.
2. WordPress מאתר `consumer_id` במרשם ודורש שהצרכן יהיה `active`.
3. WordPress דורש שה-`market_id` וה-`channel_id` יהיו ברשימות ההרשאה של אותו צרכן.
4. WordPress מאמת `key_id`, timestamp, nonce וחתימת HMAC באמצעות מפתח נגזר ייעודי לצרכן ול-scope `pos_catalog`.
5. החוזה דורש שוק, ערוץ ו-locale מוגדרים, וערוץ מסוג `kiosk` או `pos` בלבד.
6. Complete99 בוחרת רק `channel_offers` פעילות, בתוקף, ובעלות התאמה מדויקת לשוק ולערוץ.
7. כל הצעה נבדקת בזמן הבקשה מול מוצר WooCommerce מפורסם בעל SKU זהה, מטבע זהה ומחיר זהה.
8. הזמינות, סטטוס המלאי, הכמות ותמונת ברירת המחדל נקראים מ-WooCommerce בזמן הבקשה.
9. הפריטים ממוינים לפי `product_code` ומוחזרים בעמודים של עד 250 פריטים.

### מצב MyShop הנוכחי, כשל סגור

| ישות | מזהה | מצב נוכחי |
| --- | --- | --- |
| פרופיל מחבר | `connector-myshop-contract-pending` | `binding_state: contract_required`, `transport_mode: unbound` |
| ערוץ קיוסק | `channel-myshop-kiosk-il` | `contract_required` |
| ערוץ קופה | `channel-myshop-pos-il` | `contract_required` |
| צרכן מתאם | `consumer-myshop-pos-adapter` | `contract_required` |
| מפתח רשום | `myshop-pos-adapter-v1` | מזהה שמור בלבד, לא credential פעיל |
| הצעות ערוץ | `channel_offers` | המערך ריק בנתוני הפיילוט |

לכן מתאם MyShop אינו יכול לבצע קריאה פעילה כעת. בדיקת ההרשאה מחזירה `403 complete99_pos_consumer_scope` לפני קבלת הבקשה, גם אם נשלחו headers שנראים תקינים. אין fallback פתוח, אין עקיפת scope ואין הקרנת מוצרים שלא אושרו כהצעות ערוץ פעילות.

### תנאי מעבר להפעלה

המעבר ממצב חסום למצב פעיל דורש את כל התנאים הבאים יחד:

1. חוזה API רשמי ומאומת של MyShop לחשבון ולגרסה המדויקים.
2. שינוי פרופיל המחבר ל-`binding_state: bound` ול-transport מאומת מסוג `api`, `webhook`, `polling` או `batch`.
3. שינוי ערוצי MyShop ל-`active` רק לאחר בדיקת התצוגה וההתנהגות בקיוסק ובקופה.
4. שינוי הצרכן ל-`active` רק כשהמחבר כבר `bound`.
5. `key_id` ייחודי והקצאה מאובטחת של המפתח הנגזר לצרכן, בלי למסור את סוד השורש.
6. הצעות ערוץ פעילות ומאושרות שמקושרות ל-`woo_product_code` קיים ומפורסם.
7. בדיקות חתימה, scope, replay, pagination, התאמת מחיר, זמינות, ניתוק, UAT ו-rollback.

### זרימת היעד העתידית להזמנות

| הודעה לוגית | שולח יעד | מקבל יעד | תוצאה נדרשת |
| --- | --- | --- | --- |
| `catalog.item.upsert` | Complete99 | מתאם MyShop | יצירה או עדכון של פריט ותצוגתו בעץ קטגוריה ותת-קטגוריה |
| `availability.set` | Complete99 | מתאם MyShop | החלפת ערך הזמינות הקודם בערך מוחלט חדש |
| `order.created` | מתאם MyShop | Complete99 | יצירת הזמנת WooCommerce אחת בלבד ושמירת מזהה המקור |
| `order.status.changed` | מתאם MyShop או Complete99 | הצד השני | סנכרון מעבר סטטוס תקין, מתועד וללא לולאת החזרה |
| `sync.receipt` | מקבל ההודעה | שולח ההודעה | אישור קבלה, יישום, כפילות, דחייה או צורך בניסיון חוזר |

רצף זה הוא תכן עתידי בלבד. הוא ייקשר ל-transport רק לאחר אימות מסמכי הספק, מיפוי שדות ובדיקת sandbox.

## 4. חוזה POS הממומש ב-v2

### 4.1 בקשה מדויקת

ה-endpoint מקבל `POST`. הלקוח שולח `Content-Type: application/json`. גוף הבקשה חייב להיות JSON לא ריק, בגודל שאינו עולה על 524,288 bytes, ועם שבעת המפתחות הבאים בדיוק. שדות נוספים נדחים.

```json
{
  "schema": "complete99-pos-catalog-request/v1",
  "consumer_id": "consumer-myshop-pos-adapter",
  "market_id": "market-il-launch",
  "channel_id": "channel-myshop-kiosk-il",
  "locale": "locale-he-il",
  "cursor": "",
  "limit": 100
}
```

כללי החוזה:

- `schema` חייב להיות `complete99-pos-catalog-request/v1`.
- `consumer_id`, `market_id`, `channel_id` ו-`locale` חייבים להיות מזהים חוקיים וקיימים במרשם.
- `market_id` ו-`channel_id` חייבים להיכלל ב-scope של הצרכן.
- ה-locale חייב להיות משויך לשוק. בשוק ההשקה קיימים `locale-he-il` ו-`locale-en-il`.
- הערוץ חייב להיות מסוג `kiosk` או `pos`.
- `cursor` הוא מחרוזת ריקה לעמוד הראשון, או `v1:{offset}` כאשר offset כולל בין ספרה אחת לשמונה ספרות.
- `limit` הוא מספר שלם בין 1 ל-250.

הדוגמה מתארת את גוף החוזה בלבד. במצב המרשם הנוכחי היא נכשלת בכוונה עם `403`, מפני שהצרכן שמור במצב `contract_required`.

### 4.2 headers וחתימה

ארבעת ה-headers הבאים חובה:

| Header | כלל |
| --- | --- |
| `X-Complete99-Timestamp` | זמן Unix בשניות, ספרות בלבד, בחלון של 300 שניות מזמן השרת |
| `X-Complete99-Nonce` | ערך חדש לכל ניסיון, 16 עד 128 תווים מתוך `A-Z`, `a-z`, `0-9`, `_`, `-` |
| `X-Complete99-Key-Id` | התאמה מדויקת ל-`key_id` של הצרכן במרשם |
| `X-Complete99-Signature` | HMAC-SHA256 באורך 64 תווי hex קטנים |

החתימה מחושבת מעל ה-bytes המדויקים של הגוף שנשלח. אין לבצע parse ו-serialize מחדש בין חישוב החתימה לבין המשלוח.

הקלט הקנוני כולל, בסדר הבא ובשורות נפרדות, את גרסת החתימה, מתודת HTTP, הנתיב, ה-scope, הצרכן, מזהה המפתח, timestamp, nonce ו-SHA-256 של הגוף:

```text
complete99-integration-signature/v1
POST
/complete99/v1/integrations/pos/catalog
pos_catalog
consumer-myshop-pos-adapter
myshop-pos-adapter-v1
{timestamp}
{nonce}
{sha256_hex_of_exact_raw_body}
```

הנוסחה המדויקת למפתח הצרכן ולחתימה היא:

```text
derivation_input = "complete99-integration-key/v1\n"
                 + scope + "\n"
                 + consumer_id + "\n"
                 + key_id

derived_key_binary = HMAC-SHA256(
  key = protected_root_secret,
  data = derivation_input,
  binary_output = true
)

signature = lowercase_hex(HMAC-SHA256(
  key = derived_key_binary,
  data = canonical_input
))
```

ה-scope עבור endpoint זה קבוע: `pos_catalog`. סוד השורש נשמר כאפשרות מוגנת ב-WordPress וחייב להכיל לפחות 32 תווים. הוא אינו נשלח למתאם ואינו נשמר במרשם או ב-Git. לכל צרכן מקצים באופן מאובטח רק את המפתח הנגזר שלו, או broker שחותם בשמו. מפתח נגזר של צרכן אחד אינו מאשר צרכן, scope או `key_id` אחרים.

הזהויות שמשתתפות בחתימה נבדקות מול הביטוי `^[a-z][a-z0-9._-]{2,99}$`. nonce שהתקבל נשמר במשך 600 שניות במרחב המבודד לפי `consumer_id`, `key_id` ו-nonce. שימוש חוזר מחזיר `409 complete99_sync_replay`.

### 4.3 תשובת קטלוג

מבנה התשובה המדויק הוא:

```json
{
  "schema": "complete99-pos-catalog-response/v1",
  "registry_version": "<registry-version>",
  "catalog_digest": "<64-lowercase-hex-characters>",
  "consumer_id": "consumer-myshop-pos-adapter",
  "market_id": "market-il-launch",
  "channel_id": "channel-myshop-kiosk-il",
  "locale": "locale-he-il",
  "count": 1,
  "next_cursor": "",
  "items": [
    {
      "offer_id": "<active-channel-offer-id>",
      "product_code": "<exact-woocommerce-sku>",
      "sku": "<internal-commerce-code>",
      "name": "<localized-product-name>",
      "variant_name": "<localized-variant-name>",
      "price_minor": 2700,
      "currency_id": "currency-ils",
      "tax_state": "<registry-tax-state>",
      "category": "<localized-category>",
      "subcategory": "<localized-subcategory>",
      "image_url": "<approved-or-woocommerce-image-url>",
      "food_tags": [],
      "allergens": [],
      "modifiers": [],
      "availability": "in_stock",
      "stock_status": "instock",
      "stock_quantity": "1",
      "version": 1
    }
  ]
}
```

הדוגמה מציגה shape בלבד ואינה טוענת שקיימת כרגע הצעת ערוץ פעילה. `catalog_digest` הוא SHA-256 של המרשם הקנוני. `next_cursor` ריק בעמוד האחרון. `stock_quantity` הוא מחרוזת מספרית או `null`, בהתאם לניהול המלאי של WooCommerce.

### 4.4 שערי WooCommerce בזמן אמת

לכל הצעת ערוץ פעילה מופעלים כל השערים הבאים לפני שהתגובה מוחזרת:

| שער | התנהגות כשל סגור |
| --- | --- |
| WooCommerce זמין | אם פונקציות הקטלוג אינן זמינות מוחזר `503 complete99_pos_woocommerce_unavailable` |
| זהות מוצר | `woo_product_code` נפתר כ-SKU של WooCommerce; המוצר חייב להיות מפורסם וה-SKU שנקרא במצב edit חייב להיות זהה בדיוק, אחרת `409 complete99_pos_product_identity` |
| מטבע | מטבע WooCommerce חייב להתאים למטבע השוק וההצעה, אחרת `409 complete99_pos_currency` |
| מחיר | מחיר WooCommerce מומר ליחידות מטבע קטנות וחייב להיות זהה ל-`price_minor`, אחרת `409 complete99_pos_price` |
| זמינות | `in_stock` מוחזר רק אם המוצר גם במלאי וגם ניתן לרכישה; בכל מצב אחר מוחזר `out_of_stock` |
| מלאי ותמונה | `stock_status`, `stock_quantity` ותמונת ברירת המחדל נקראים מהמוצר החי; תמונת `kiosk_projection` מאושרת גוברת אם הוגדרה |

אין partial success במקרה של הצעה פעילה שאינה מתאימה ל-WooCommerce. הבקשה כולה נכשלת כדי שלא להקרין מחיר או זהות מוצר שגויים לקופה.

### 4.5 קודי חוזה מרכזיים

| קוד | HTTP | משמעות |
| --- | --- | --- |
| `complete99_integration_unconfigured` | 503 | סוד השורש חסר או קצר מ-32 תווים |
| `complete99_integration_identity` | 401 | scope, צרכן או key ID אינם חוקיים |
| `complete99_integration_size` | 413 | גוף ריק או גדול מהמותר |
| `complete99_integration_key` | 401 | ה-key ID ב-header אינו תואם למרשם |
| `complete99_integration_time` | 401 | timestamp אינו חוקי או מחוץ לחלון |
| `complete99_integration_headers` | 401 | nonce או חתימה בפורמט שגוי |
| `complete99_integration_signature` | 401 | החתימה אינה תואמת |
| `complete99_sync_replay` | 409 | nonce כבר נוצל |
| `complete99_pos_consumer_scope` | 403 | הצרכן אינו פעיל או אינו מורשה לשוק או לערוץ |
| `complete99_pos_catalog_contract` | 422 | גוף הבקשה אינו תואם לסכמה המדויקת |
| `complete99_pos_catalog_scope` | 404 | שוק, ערוץ או locale אינם מוגדרים יחד |
| `complete99_pos_catalog_channel` | 422 | הערוץ אינו `kiosk` או `pos` |

### 4.6 מעטפת אירועים ניטרלית לתכן העתידי

המעטפת הבאה מושפעת ממבנה CloudEvents כדי לשמור על ניידות בין HTTP, webhook, תור הודעות או קובץ אצווה. היא אינה חלק מה-endpoint הממומש ב-v2, ואינה טענה ש-MyShop תומכת ב-CloudEvents.

```json
{
  "specversion": "1.0",
  "id": "6f1572ec-2419-45f8-9f7e-0d8c9187f322",
  "type": "catalog.item.upsert",
  "source": "urn:complete99:woocommerce",
  "subject": "sku:C99-DISH-0001",
  "time": "2026-08-06T08:30:00Z",
  "datacontenttype": "application/json",
  "dataschema": "urn:complete99:schema:catalog-item:v1",
  "correlationid": "8bcfc836-c13c-4ac2-996c-f43c50c25e6c",
  "causationid": null,
  "idempotencykey": "c99|catalog|C99-DISH-0001|42",
  "payload_digest": "sha256:HEX_DIGEST",
  "tenantid": "complete99",
  "branchid": "complete99-main",
  "revision": 42,
  "data": {}
}
```

שדות התכן העתידי:

| שדה | כלל |
| --- | --- |
| `specversion` | גרסת המעטפת, מתחילה ב-`1.0` |
| `id` | מזהה גלובלי ייחודי להודעה |
| `type` | אחד מחמשת הסוגים הלוגיים המוצעים במסמך |
| `source` | מזהה יציב של המערכת השולחת, לא כתובת עם סוד |
| `subject` | הישות המרכזית, למשל SKU או מזהה הזמנה |
| `time` | זמן UTC בפורמט RFC 3339 |
| `datacontenttype` | `application/json` |
| `dataschema` | גרסת סכמת התוכן |
| `correlationid` | מזהה משותף לכל רצף עסקי |
| `causationid` | מזהה ההודעה שגרמה להודעה הנוכחית, או `null` להודעה ראשונה |
| `idempotencykey` | מפתח יציב שמונע יישום כפול |
| `payload_digest` | SHA-256 של JSON קנוני של `data` |
| `tenantid` | `complete99` |
| `branchid` | מזהה סניף קנוני, חובה בכל הודעה תפעולית |
| `revision` | מספר גרסה מונוטוני לישות ולסניף |
| `data` | גוף ההודעה לפי הסכמה המתאימה |

אין להכניס למזהים, למפתחות idempotency, לכתובות לוג או ל-subject מידע אישי, סוד, מספר כרטיס או אסימון תשלום.

## 5. זהות SKU

בחוזה POS הממומש, `product_code` הוא ה-SKU המדויק של WooCommerce שמגיע מן השדה `woo_product_code` במרשם. השדה `sku` בתשובה הוא הקוד המסחרי הפנימי של מרשם Complete99. מזהה WordPress מספרי אינו יוצא בחוזה ואינו תחליף ל-SKU, מפני שמספרי מסד נתונים יכולים להשתנות בין סביבות.

כללים:

- SKU אחד מזהה פריט מכירתי אחד או וריאציה מכירתית אחת.
- SKU אינו ממוחזר לאחר ארכוב מוצר.
- ההשוואה היא מדויקת ותלוית רישיות עד ש-MyShop תאשר כלל אחר בכתב.
- אין התאמה לפי שם, slug, תמונה או חיפוש מקורב.
- מוצר משתנה מקבל SKU למשפחת המוצר וכל וריאציה מכירתית מקבלת SKU נפרד.
- במתאם העתידי כל רשומת MyShop תשמור את `product_code` בלי שינוי וכן `external_id` קנוני של Complete99, אם שדה כזה נתמך ומאומת בחוזה הספק.
- שינוי SKU אינו עדכון רגיל. הוא תהליך מעבר מפורש עם מיפוי הישן לחדש ואישור התאמה.

תבניות idempotency מוצעות להודעות העתידיות:

| אירוע | תבנית |
| --- | --- |
| קטלוג | `c99|catalog|{sku}|{revision}` |
| זמינות | `c99|availability|{branch_id}|{sku}|{revision}` |
| הזמנה | `myshop|order|{branch_id}|{external_order_id}` |
| סטטוס הזמנה | `{source}|order-status|{external_order_id}|{status_revision}` |
| קבלה | `{receiver}|receipt|{original_event_id}` |

## 6. הקרנת קטגוריה ותת-קטגוריה במתאם העתידי

ה-endpoint הממומש כבר מחזיר `category` ו-`subcategory` מקומיות מתוך `kiosk_projection`, בהתאם לעומק 2 שהוגדר לערוצי MyShop. לאחר אימות חוזה הספק, כל פריט יגיע למתאם MyShop עם מיקום היררכי מלא של שתי רמות לפחות. אסור להסיק שיוך ממילות שם המוצר.

בתכן העתידי `catalog.item.upsert` נושא מערך `placements`. כל placement כולל מסלול מסודר מהורה לילד, מזהים יציבים, שמות בעברית ובאנגלית וסדר תצוגה. ניתן לשייך פריט ליותר ממסלול אחד, אך רק מסלול אחד מסומן כראשי.

```json
{
  "sku": "C99-DISH-0001",
  "entity_id": "dish:sabich-pita",
  "kind": "menu_item",
  "status": "active",
  "names": {
    "he": "סביח בפיתה",
    "en": "Sabich in pita"
  },
  "placements": [
    {
      "primary": true,
      "path": [
        {
          "id": "menu:pita",
          "parent_id": null,
          "depth": 1,
          "labels": { "he": "מנות בפיתה", "en": "Pita dishes" },
          "sort_order": 10
        },
        {
          "id": "menu:pita:vegetarian",
          "parent_id": "menu:pita",
          "depth": 2,
          "labels": { "he": "צמחוניות", "en": "Vegetarian" },
          "sort_order": 20
        }
      ],
      "item_sort_order": 30
    }
  ],
  "media": {
    "primary_image_url": "https://complete99.co.il/example.webp",
    "alt": { "he": "סביח בפיתה", "en": "Sabich in pita" }
  },
  "price": {
    "currency": "ILS",
    "amount_minor": 2700,
    "tax_included": true
  },
  "revision": 42
}
```

הדוגמה מציגה מבנה בלבד. היא אינה קובעת SKU, מחיר, כתובת תמונה או קטגוריית ייצור בפועל.

### סדר יישום

1. MyShop פותרת או יוצרת את צומת הקטגוריה לפי `id`.
2. MyShop פותרת או יוצרת את תת-הקטגוריה לפי `id` ו-`parent_id`.
3. MyShop משייכת את הפריט לפי SKU.
4. MyShop שומרת את סדר ההורה, הילד והפריט.
5. MyShop קוראת את התוצאה בחזרה ומחזירה `sync.receipt` עם המזהים המקומיים שנוצרו.

תמיכה אמיתית בשתי רמות, כולל תצוגת קיוסק, סדר, ארכוב ושפות, היא בדיקת קבלה. אתר MyShop הציבורי מאשר ניהול מוצרים וקטגוריות, אך אינו מפרסם סכמת API או מגבלת עומק. לכן יכולת זו עדיין דורשת אישור טכני.

## 7. `catalog.item.upsert`, תכן עתידי

מטרה: ליצור או לעדכן הקרנת פריט בלי ליצור כפילות.

שדות `data` נדרשים:

- `sku`
- `entity_id`
- `kind`: `menu_item`, `retail_product` או `variation`
- `status`: `active` או `archived`
- `names.he` ו-`names.en`
- `descriptions.he` ו-`descriptions.en`, אם אושרו לפרסום
- `placements`
- `media.primary_image_url` ו-alt דו-לשוני
- `price.currency`
- `price.amount_minor`, מספר שלם ביחידות המטבע הקטנות
- `price.tax_included`
- `modifiers` או `variation_options`, רק אם MyShop מאשרת את הסכמה
- `allergen_codes`, אם נדרש במסך המכירה ונבדק מקצועית
- `revision`
- `effective_at`

כלל ארכוב: אין למחוק פריט שכבר הופיע בהזמנה. שולחים `status: archived`, מסירים אותו ממכירה, ושומרים את הזהות לצורכי היסטוריה והתאמה.

## 8. `availability.set`, תכן עתידי

מטרה: לקבוע זמינות וכמות מכירה מוחלטת לסניף ול-SKU.

```json
{
  "sku": "C99-DISH-0001",
  "stock_location_id": "complete99-main",
  "quantity_type": "sellable_absolute",
  "quantity": 7,
  "availability_state": "in_stock",
  "backorders_allowed": false,
  "effective_at": "2026-08-06T08:35:00Z",
  "revision": 118,
  "reason_code": "woocommerce_reconciliation"
}
```

כללים:

- `quantity` הוא תמיד מספר שלם, אפס או חיובי.
- הערך הוא מוחלט. הוא מחליף את הערך הקודם ואינו תוספת או הפחתה.
- אין לשלוח `+3`, `-1`, delta או פקודת increment.
- revision ישן נדחה ואינו יכול לדרוס ערך חדש.
- אותה revision עם digest זהה היא כפילות בטוחה.
- אותה revision עם digest שונה היא התנגשות ונדרשת בדיקה.
- כמות אפס חייבת להשבית הוספה לסל בכל מסכי MyShop הרלוונטיים.
- MyShop אינה מפחיתה את המלאי הקנוני ישירות. הזמנה חוזרת ל-WooCommerce, WooCommerce מיישמת את תנועת המלאי, ואז נשלח ערך מוחלט חדש.
- במקרה של הזמנה שאינה מתאימה לכמות הקנונית, אין למחוק את ההזמנה ואין ליצור מלאי שלילי בשקט. שומרים את האירוע, מונעים מכירה נוספת של הפריט ומחזירים קבלה עם קוד התאמה מפורש.

## 9. `order.created`, תכן עתידי

מטרה: להעביר הזמנה שנוצרה ב-MyShop וליצור רשומת WooCommerce אחת בלבד.

שדות נדרשים:

- `external_order_id`
- `source_channel`: למשל `myshop_kiosk` או `myshop_pos`, רק לאחר אישור שמות הערוצים
- `branch_id`
- `created_at`
- `locale`
- `fulfillment_type`
- `currency`
- `prices_include_tax`
- `lines[]`
- `totals`
- `payment`
- `status`
- `source_revision`

כל שורת הזמנה כוללת:

- `external_line_id`
- `sku`
- `quantity`
- `unit_amount_minor`
- `subtotal_minor`
- `discount_minor`
- `tax_minor`
- `total_minor`
- `modifier_lines`, רק לאחר שמיפוי המודיפיירים אושר

WooCommerce בודקת לפני יצירה:

1. שהאירוע חדש או כפילות זהה.
2. שכל SKU קשור למוצר מדויק.
3. שהמטבע והסכומים תקינים ונסכמים בדיוק.
4. שהמחיר, המס וההנחה ניתנים להתאמה למקור.
5. שההזמנה אינה מכילה שדה תשלום אסור.
6. שלא קיימת כבר הזמנה עם אותו `branch_id` ו-`external_order_id`.

לאחר יצירה נשמרים במטא פרטי המקור, מזהה האירוע, digest, מזהה ההזמנה החיצונית וה-revision. אישור `applied` נשלח רק לאחר קריאה חוזרת של הזמנת WooCommerce.

### תשלום: אסמכתה בלבד

```json
{
  "payment": {
    "status": "paid",
    "provider_code": "provider-assigned-code",
    "reference": "opaque-provider-reference",
    "amount_minor": 2700,
    "currency": "ILS",
    "confirmed_at": "2026-08-06T08:42:00Z"
  }
}
```

מותר להעביר רק אסמכתה אטומה של ספק הסליקה וסטטוס עסקי שניתן לאמת. אסור להעביר או לשמור במעטפת מספר כרטיס מלא, CVV, פס מגנטי, PIN, סוד API, מפתח סליקה, אסימון תשלום שמאפשר חיוב, או פרטי הזדהות של חשבון הספק.

סטטוס `paid` אינו מתקבל על סמך טקסט חופשי. MyShop חייבת לתעד מהו מקור הסטטוס, מתי התקבל, האם הוא סופי, וכיצד מאמתים אותו מול ספק הסליקה.

## 10. `order.status.changed`, תכן עתידי

מטרה: לשקף מעבר סטטוס תפעולי בלי לדרוס היסטוריה ובלי ליצור לולאת אירועים.

```json
{
  "external_order_id": "MS-123456",
  "woocommerce_order_id": 0,
  "from_status": "accepted",
  "to_status": "preparing",
  "status_revision": 3,
  "changed_at": "2026-08-06T08:45:00Z",
  "reason_code": "kds_started",
  "origin": "myshop"
}
```

כללים:

- מיפוי הסטטוסים של MyShop ל-WooCommerce חייב להימסר ולאושר לפני חיבור.
- כל מעבר נבדק מול טבלת מעברים מותרת.
- סטטוס ישן או revision ישנה אינם דורסים מצב חדש.
- ביטול, החזר ותשלום דורשים כללי התאמה נפרדים ואינם נגזרים מסטטוס הכנה בלבד.
- אירוע נגזר מחזיר את `causationid` של האירוע המקורי. המקבל אינו מהדהד אותו שוב כמקור חדש.
- WooCommerce שומרת את הסטטוס הקנוני ואת היסטוריית המעברים.

## 11. `sync.receipt`, תכן עתידי

מטרה: לספק הוכחה עמידה למה קרה לאירוע שנשלח.

```json
{
  "original_event_id": "6f1572ec-2419-45f8-9f7e-0d8c9187f322",
  "original_event_type": "catalog.item.upsert",
  "original_payload_digest": "sha256:HEX_DIGEST",
  "status": "applied",
  "receiver": "myshop",
  "received_at": "2026-08-06T08:30:01Z",
  "applied_at": "2026-08-06T08:30:02Z",
  "receiver_revision": 42,
  "receiver_ids": {
    "item_id": "vendor-item-id",
    "category_id": "vendor-category-id",
    "subcategory_id": "vendor-subcategory-id"
  },
  "errors": []
}
```

ערכי `status`:

| סטטוס | משמעות |
| --- | --- |
| `accepted` | האירוע נשמר בתור עמיד אך עדיין לא הוכח כמיושם |
| `applied` | השינוי יושם ונקרא בחזרה בהצלחה |
| `duplicate` | אותו idempotency key ואותו digest כבר יושמו |
| `rejected` | שגיאה קבועה, למשל סכמה, הרשאה או זהות SKU |
| `retryable` | תקלה זמנית, למשל עומס או תלות שאינה זמינה |

Complete99 מסמנת משלוח כמושלם רק לאחר `applied`, או לאחר `duplicate` עם digest זהה וקריאת מצב תואמת. תשובת HTTP מוצלחת לבדה אינה הוכחת יישום.

## 12. idempotency, סדר, ניסיונות חוזרים והתאמה בתכן העתידי

### idempotency

- המסירה היא לפחות פעם אחת, אך ההשפעה העסקית היא פעם אחת לכל מפתח idempotency.
- ניסיון חוזר שולח את אותם bytes של גוף האירוע, אותו `id`, אותו `idempotencykey` ואותו digest.
- nonce או חתימת תעבורה יכולים להתחדש בכל ניסיון, בלי לשנות את גוף האירוע.
- אותו מפתח עם digest אחר נדחה כהתנגשות.
- כל מערכת שומרת outbox לפני שליחה ו-inbox לפני יישום.
- קבלה נשמרת באופן עמיד וניתנת לקריאה חוזרת לצורך התאמה.

### סדר וגרסאות

- revision עולה בנפרד לכל ישות, סניף וסוג מצב.
- אירוע עתידי יכול להישמר עד השלמת פער, אך אינו מיושם מעל פער לא מוסבר.
- אירוע ישן מוחזר כ-`duplicate` או `rejected` לפי ההתאמה ל-digest הקנוני.
- זמן האירוע אינו תחליף ל-revision.

### ניסיונות חוזרים

- מנסים שוב רק לאחר timeout, כשל רשת, `408`, `425`, `429` או `5xx`, ובהתאם ל-`Retry-After` אם התקבל.
- לא מנסים שוב אוטומטית לאחר כשל הזדהות, סכמה, הרשאה או התנגשות זהות עד שהגורם תוקן.
- משתמשים בהשהיה מעריכית עם jitter ותקרה ניתנת להגדרה.
- האירוע נשאר בתור עמיד עד קבלה סופית. אסור למחוק הזמנה בגלל תום חלון retry.
- כשל חוזר נכנס ליומן כשלים עם payload digest, ספירת ניסיונות, זמן ניסיון הבא וקוד שגיאה, בלי סודות ובלי פרטי כרטיס.

### התאמה מלאה

בנוסף לאירועים שוטפים נדרשת ריצת reconciliation יזומה:

1. השוואת כל SKU פעיל בין WooCommerce ל-MyShop.
2. השוואת מסלול קטגוריה ותת-קטגוריה וסדרי תצוגה.
3. השוואת מחיר, מטבע, מס, תמונה וסטטוס ארכוב.
4. השוואת זמינות מוחלטת לכל SKU וסניף.
5. השוואת הזמנות לפי מזהה מקור, סכום וסטטוס.
6. תיקון דרך אירועים חדשים עם revision חדש, לא באמצעות עריכת מסד נתונים ידנית.

## 13. הזדהות, סודות והרשאות מינימליות

יש להפריד בין שני גבולות הזדהות:

1. בגבול הכניסה ל-Complete99, חתימת HMAC מצומצמת לצרכן כבר ממומשת ומוגדרת בדיוק בסעיף 4.
2. בגבול שבין שכבת המתאם לבין שירותי MyShop, שיטת ההזדהות של הספק טרם אומתה. אין לבחור OAuth, HMAC, mTLS, מפתח API או Basic Auth בשם MyShop עד לקבלת מסמך רשמי.

### הרשאת הצרכן ב-Complete99

- לכל צרכן יש `consumer_id`, `key_id`, `connector_profile_id`, רשימת `market_ids`, רשימת `channel_ids`, `state` ו-`credential_version`.
- `key_id` חייב להיות ייחודי בכל המרשם.
- כל ערוץ של הצרכן חייב להיות שייך לאותו מחבר, וכל שוק של הערוץ חייב להיכלל ב-scope השווקים של הצרכן.
- רק צרכן `active` יכול לעבור את permission callback.
- צרכן יכול להיות `active` רק אם המחבר שלו `internal_bound` או `bound`.
- הגדלת `credential_version` ו-`key_id` חדש מאפשרות סיבוב credential בלי להרחיב את ה-scope.
- סוד השורש אינו credential של MyShop ואינו יוצא מ-WordPress. המתאם מקבל רק מפתח נגזר ייעודי או שירות חתימה מצומצם.

### דרישות לחיבור הספק שייבחר

- תקשורת שרת לשרת דרך HTTPS בלבד.
- סוד נפרד לסביבת בדיקות ולייצור.
- סוד נפרד לכל כיוון תעבורה ככל שהספק מאפשר.
- הרשאות צרות בלבד: קריאת קטלוג, קריאת זמינות, כתיבת הזמנה, כתיבת סטטוס וכתיבת קבלה לפי הצורך.
- אין לתת חשבון מנהל WordPress או מפתח WooCommerce רחב אם endpoint ייעודי יכול לבצע פעולה צרה.
- הסודות נשמרים מחוץ ל-Git, מחוץ ל-JavaScript של הדפדפן ומחוץ ללוגים.
- נדרשים תאריך יצירה, שימוש אחרון, סיבוב, ביטול ונתיב התאוששות לכל credential.
- אם MyShop תומכת בחתימת גוף, timestamp ו-nonce, יש לאמת את האלגוריתם וה-bytes המדויקים מול תיעוד הספק. אין להחליף אותם אוטומטית בחתימת Complete99.
- allowlist של כתובות IP היא שכבת הגנה נוספת בלבד, לא תחליף לחתימה ולהרשאה.
- לוגים משמיטים Authorization, cookies, סודות, אסימוני תשלום ונתוני כרטיס.
- חשבון ייעודי אינו מקבל גישה ללקוחות, עובדים, קמפיינים, ספקים או תוכן פרטי שאינו נדרש למחבר.

WordPress תומכת רשמית ב-Application Passwords לשימוש API מעל HTTPS. WooCommerce תומכת במפתחות REST ובהודעות webhook חתומות. אלה אפשרויות תשתית של WordPress ו-WooCommerce בלבד, ולא הוכחה ש-MyShop תומכת בהן ולא תחליף לחתימת הצרכן הממומשת ב-endpoint זה.

## 14. פרטיות וגבולות מידע

endpoint הקטלוג הממומש אינו מעביר פרופילי לקוחות, אנשי קשר, עובדים, ספקים, קמפיינים, הזמנות, תשלומים או מידע בריאותי.

בתכן העתידי הודעת הזמנה תכלול רק את הנתונים הדרושים לייצור, התאמה וחשבונאות. אם נדרשים שם, טלפון, כתובת, מועדון או משלוח, יש להוסיף אותם רק לאחר קבלת מפת נתונים, בסיס חוקי, הסכם עיבוד, כללי שמירה ומחיקה, והרשאה מצומצמת. אין להכניס שדות כאלה לסכמה בדרך של ניחוש.

## 15. מטריצת מאומת ולא מאומת

| נושא | מצב | ראיה נוכחית | משמעות לחיבור |
| --- | --- | --- | --- |
| מרשם מסחרי v2 | מאומת בקוד המאגר | `class-complete99-culinary-commerce.php` ו-`culinary-commerce-pilot.php` | סכמת `complete99-culinary-commerce-registry/v2` נאכפת לפני הקרנה |
| endpoint קטלוג POS ניטרלי | מאומת בקוד המאגר | `POST /complete99/v1/integrations/pos/catalog` | משטח קריאה ממומש של Complete99, לא endpoint של MyShop |
| חתימת צרכן נגזרת | מאומת בקוד המאגר | `Complete99_REST::verify_scoped_integration_signature()` | החתימה קושרת version, method, route, scope, consumer, key ID, timestamp, nonce ו-body hash |
| scope לפי שוק וערוץ | מאומת בקוד המאגר | `verify_pos_signature()` ו-`validate_integration_consumers()` | צרכן לא פעיל או מחוץ ל-scope נכשל ב-403 |
| הקרנת SKU, מחיר ומלאי חיים | מאומת בקוד המאגר | `pos_items()` ו-`woo_runtime_projection()` | SKU, פרסום, מטבע ומחיר נבדקים מול WooCommerce; זמינות ומלאי נקראים בזמן אמת |
| מצב MyShop במרשם | מאומת בקוד המאגר | `connector-myshop-contract-pending` והצרכן השמור | `contract_required` ו-`unbound`; אין גישה פעילה ואין טענה לחיבור חי |
| הצעות MyShop פעילות | אינן קיימות בפיילוט הנוכחי | `channel_offers` הוא מערך ריק | אין מוצר שמותר להקרין לערוצי MyShop עד אישור והפעלה מפורשים |
| MyShop מפעילה אתר, אפליקציה וקיוסק שירות עצמי | מאומת כהצהרת מוצר ציבורית | האתר הרשמי בישראל ובארצות הברית | מתאים ליעד ההקרנה, אינו תחליף למסמך API |
| הזמנות מערוצים שונים מגיעות לדשבורד ול-KDS של MyShop | מאומת כהצהרת מוצר ציבורית | האתר הרשמי של MyShop | מאפשר לתכנן `order.created`, עדיין נדרשת סכמת אירוע |
| MyShop מציגה ניהול מוצרים וקטגוריות | מאומת כהצהרת מוצר ציבורית | דפי האפליקציות הרשמיים | תומך בעקרון קטלוג, לא מאשר עומק היררכיה או API |
| MyShop מציגה ריבוי סניפים | מאומת כהצהרת מוצר ציבורית | שאלות ותשובות באתר הישראלי | מחייב מיפוי branch ID רשמי |
| MyShop מציגה את WooCommerce ברשימת האינטגרציות | מאומת כהצהרת מוצר ציבורית | האתר הבינלאומי הרשמי | מחייב לקבל מסמך מדויק על כיוון וסוג התמיכה בחשבון הישראלי |
| כתובת API, endpoint או base URL של MyShop | לא מאומת | לא נמצא תיעוד API ציבורי רשמי | אין לקודד כתובת לפני מסמך ספק |
| שיטת auth וחתימת webhook של MyShop | לא מאומת | לא נמצא תיעוד רשמי ציבורי | אין לפתוח חיבור ייצור |
| תמיכה בקטגוריה ותת-קטגוריה דרך API | לא מאומת | האתר מציג קטגוריות בלבד | נדרשת הוכחת sandbox וקיוסק |
| שמירת SKU ו-external ID ללא שינוי | לא מאומת | לא נמצא חוזה שדות | נדרשת טבלת mapping רשמית |
| תמיכה בכמות מלאי מוחלטת לפי סניף | לא מאומת | לא נמצא חוזה מלאי | נדרשת בדיקת `availability.set` |
| webhook להזמנה ולסטטוס | לא מאומת | לא נמצא קטלוג אירועים | נדרשים schema, signing, retries ו-replay |
| משמעות סטטוס תשלום ואסמכתת ספק | לא מאומת | לא נמצא חוזה סליקה | אין לסמן הזמנה כמשולמת לפני אימות |
| מגבלות rate, batch וגודל payload | לא מאומת | לא נמצא תיעוד ציבורי | נדרשים מספרים רשמיים ובדיקת עומס |
| התנהגות offline של הקיוסק וה-KDS | לא מאומת | לא נמצא חוזה טכני | נדרשים buffer, TTL, replay והתאוששות |
| endpoint המלאי הפרטי של Complete99 | מאומת בקוד המאגר | `class-complete99-inventory-bridge.php` | מקבל מזהים מדויקים, גרסאות וכמויות מוחלטות; אינו endpoint של MyShop ואינו חלק מקריאת קטלוג POS |
| חוזה הודעות דו-כיווני מול MyShop | לא מאומת ולא ממומש | אין חוזה API ספק | שמות ההודעות בסעיפים 6 עד 12 הם תכן מתאם בלבד |

## 16. מסמכים ופרטים מדויקים שיש לבקש מ-MyShop

יש לבקש חבילה אחת מסודרת ועדכנית לחשבון Complete99:

1. מסמך OpenAPI 3.x מלא, או תיעוד API רשמי שווה ערך.
2. Postman collection ודוגמאות request/response שמותאמות לגרסת הייצור הנוכחית.
3. base URL נפרד ל-sandbox ול-production, ללא credentials בתוך המסמך.
4. מדיניות versioning, changelog, deprecation וחלון הודעה על שינוי שובר תאימות.
5. מסמך authentication מלא: סוג credential, scopes, חתימה, timestamp, nonce, clock skew, IP allowlist, סיבוב וביטול.
6. קטלוג webhooks: שמות אירועים, JSON Schema, headers, חתימה, סדר, כפילויות, retries, timeout, replay ויומן מסירות.
7. סכמת קטלוג: מוצר, וריאציה, modifier, combo, תוספת, תמונה, מחיר, מס, מבצע, ארכוב וסדר תצוגה.
8. סכמת קטגוריות: עומק מרבי, `parent_id`, ריבוי מסלולים, סדר, שמות HE/EN והתנהגות מחיקה או ארכוב.
9. חוזה זהות: SKU, external ID, כללי רישיות, אורך, תווים מותרים, uniqueness ומדיניות שינוי מזהה.
10. חוזה מלאי: כמות מוחלטת או delta, יחידת מלאי, מלאי לפי סניף, reservations, oversell, backorders, bulk update ו-readback.
11. חוזה הזמנה: מזהה ייחודי, שורות, וריאציות, modifiers, הנחות, מס, rounding, tip, fulfillment, מקור ערוץ וזמני אירוע.
12. מפת סטטוסים מלאה של הזמנה, מטבח, ביטול, כשל, החזר חלקי והחזר מלא.
13. חוזה סליקה: משמעות `authorized`, `paid`, `failed`, `refunded`, מבנה האסמכתה, אימות מול הספק וגבול PCI.
14. חוזה ריבוי סניפים: tenant ID, branch ID, מחירים מקומיים, מלאי מקומי, שעות, timezone ותפריט מקומי.
15. מגבלות רשמיות: requests לדקה, concurrent requests, גודל body, מספר פריטים באצווה, pagination וזמן שמירת לוג.
16. התנהגות kiosk ו-KDS בזמן ניתוק: cache TTL, הזמנות offline, שמירת תשלום, replay, זיהוי כפילות ותצוגת last sync.
17. מסמך אבטחה ופרטיות: DPA, מיקום נתונים, subprocessors, encryption, retention, export, deletion, incident notification ו-audit logs.
18. מסמך התמיכה הספציפית ב-WooCommerce: מי יוזם sync, אילו ישויות נתמכות, האם החיבור דו-כיווני ומה אינו נתמך.
19. export מלא של הקטלוג הקיים של Complete99 מתוך MyShop, כולל category IDs, item IDs, SKU אם קיים, modifiers, prices, images וסטטוס.
20. רשימת tenant, branch, kiosk ו-KDS IDs של סביבת Complete99, יחד עם גרסאות התוכנה או הקושחה הרלוונטיות.
21. חשבון sandbox, סניף בדיקה, קיוסק בדיקה ופרטי בדיקת סליקה שאינם מאפשרים חיוב אמיתי.
22. checklist רשמי של MyShop ל-UAT, מעבר לייצור, rollback, תמיכה ותהליך דיווח תקלות.
23. איש קשר טכני וערוץ תקרית שמוסמכים לאשר payloads, מיפויים ושינויי גרסה.

### שאלות שחייבות תשובה כתובה

1. האם MyShop יכולה לקבל ולשמור SKU חיצוני ייחודי בלי לשנות אותו?
2. האם ניתן להקרין שתי רמות נפרדות של קטגוריה ותת-קטגוריה בקיוסק, באפליקציה וב-Menu TV?
3. האם עדכון קטלוג הוא upsert idempotent, ומהו מפתח הכפילות?
4. האם מלאי מתקבל כערך מוחלט לפי SKU וסניף?
5. האם ניתן לקרוא בחזרה את הערך שיושם ולקבל מזהה מסירה?
6. האם הזמנה נשלחת ב-webhook לפני או אחרי אישור תשלום?
7. מהו המזהה היציב של הזמנה, ומה קורה כשאותו webhook נשלח שוב?
8. כיצד חתימת webhook נוצרת, ועל אילו bytes בדיוק מחשבים אותה?
9. מהם retry, timeout, replay ו-ordering guarantees של MyShop?
10. כיצד ממופים modifiers ו-combos ל-SKU או לשורת הזמנה?
11. כיצד MyShop מייצגת ביטול, החזר חלקי, החזר מלא וטיפ?
12. איזו אסמכתת תשלום מותר לשמור, וכיצד בודקים שהיא שייכת להזמנה ולסכום?
13. האם ניתן לבטל עריכת קטלוג ידנית ב-MyShop כדי למנוע שני מקורות אמת?
14. כיצד MyShop מתנהגת כאשר WordPress אינו זמין בזמן הזמנה?
15. כיצד מבצעים full export ו-full reconciliation ללא השבתה?
16. אילו נתונים מועברים לספקי משנה, היכן הם נשמרים ולכמה זמן?

## 17. בדיקות קבלה לפני חיבור ייצור

### 17.1 חוזה Complete99 v2

1. צרכן `contract_required` או `disabled` נדחה ב-403 גם כאשר מבנה הבקשה תקין.
2. צרכן פעיל אינו יכול לקרוא שוק או ערוץ שאינם ב-scope שלו.
3. שינוי של byte אחד בגוף, method, route, scope, consumer ID, key ID, timestamp או nonce מבטל את החתימה.
4. מפתח נגזר של צרכן אחד אינו יכול לחתום עבור צרכן אחר.
5. timestamp מחוץ לחלון 300 שניות נדחה, ו-nonce חוזר בתוך 600 שניות נדחה ב-409.
6. גוף ריק או גדול מ-524,288 bytes נדחה, ושדה JSON נוסף נדחה ב-422.
7. `limit` מחוץ לטווח 1 עד 250 או cursor שאינו בתבנית `v1:{offset}` נדחים.
8. locale שאינו משויך לשוק וערוץ שאינו `kiosk` או `pos` נדחים.
9. רק הצעת ערוץ `active` ובחלון התוקף הנכון נכנסת להקרנה.
10. SKU חסר, SKU לא זהה או מוצר שאינו מפורסם גורמים לכשל 409 לכל הבקשה.
11. חוסר התאמה במטבע או במחיר גורם לכשל 409 ואינו מחזיר מחיר משוער.
12. שינוי מלאי ב-WooCommerce משתקף בבקשה הבאה בלי עריכת מרשם.
13. מוצר שאינו גם במלאי וגם purchasable מוחזר כ-`out_of_stock`.
14. pagination יציב, הפריטים ממוינים לפי `product_code`, ו-`catalog_digest` זהה לאותו מרשם קנוני.
15. לוגים אינם חושפים סוד שורש, מפתח נגזר, חתימה, cookies או מידע אישי.

### 17.2 מתאם MyShop ו-UAT עתידי

1. SKU חדש נוצר פעם אחת בלבד גם לאחר שליחת אותה הודעה שלוש פעמים.
2. שינוי שם בעברית ובאנגלית מתעדכן בלי לדרוס שפה אחרת.
3. קטגוריה ותת-קטגוריה מוצגות כשתי רמות בקיוסק, עם סדר נכון.
4. שינוי placement מעביר פריט בלי להשאיר כפילות או יתום.
5. ארכוב מוצר מסיר אותו ממכירה ושומר הזמנות היסטוריות.
6. זמינות מוחלטת עוברת ברצף `7`, `2`, `0`, והערך האחרון בלבד נשאר פעיל.
7. revision ישנה אינה דורסת revision חדשה.
8. `order.created` כפול יוצר הזמנת WooCommerce אחת בלבד.
9. כל שורת הזמנה נקשרת ל-SKU המדויק, כולל וריאציה ו-modifier מאושרים.
10. subtotal, discount, tax, tip ו-total נסכמים בדיוק ביחידות מטבע קטנות.
11. הודעת תשלום מכילה אסמכתה בלבד ואינה מכילה מידע כרטיס או credential.
12. מעבר סטטוס הלוך וחזור אינו יוצר לולאת webhooks.
13. timeout גורם ל-retry בטוח עם אותו digest.
14. כשל סכמה נדחה ואינו נשלח שוב ללא תיקון.
15. נפילת WordPress או MyShop אינה מאבדת הזמנה שכבר התקבלה בקיוסק.
16. reconciliation מלא מזהה חסר, כפילות, מחיר שונה, קטגוריה שונה ומלאי שונה.
17. ביטול credential עוצר גישה בלי לפגוע בחשבונות מנהל אחרים.
18. בדיקות HE ו-EN עוברות בקיוסק, כולל RTL, תמונות, מחירים ושמות קטגוריה.
19. עומס מוסכם עובר בתוך rate limits רשמיים וללא אובדן הודעות.

חיבור ייצור מאושר רק לאחר שבדיקות שתי השכבות תועדו מול sandbox, מסמכי הספק והגרסה המדויקת שמופעלת בחשבון Complete99.

## 18. מקורות רשמיים

### MyShop

- [האתר הרשמי בישראל](https://my-shop.co.il/): הצהרות על אתר ממותג, אפליקציה, קיוסק, KDS, ריבוי סניפים ואינטגרציות.
- [עמוד הקיוסקים הרשמי](https://my-shop.co.il/kiosks/): תיאור קיוסק שירות עצמי והעברת הזמנות לדשבורד.
- [האתר הרשמי בארצות הברית ובקנדה](https://myshoptechnologies.com/): רשימת ערוצים ואינטגרציות, כולל WooCommerce, וכן קיוסק, KDS ו-Menu TV.
- [עמוד האפליקציה למסעדות ופיצריות](https://myshoptechnologies.com/apps-for-restaurants/pizzerias/): הצהרה על הוספה, הסרה ועריכה של מוצרים וקטגוריות.

דפי MyShop הם דפי מוצר ושיווק. הם מאמתים יכולות מוצהרות, אך אינם מחליפים חוזה API, JSON Schema, SLA או אישור תמיכה בחשבון הספציפי.

### WordPress ו-WooCommerce

- [WooCommerce APIs](https://developer.woocommerce.com/docs/apis/): הבחנה בין WC REST API הפרטי לבין Store API הציבורי.
- [WooCommerce REST API v3](https://developer.woocommerce.com/docs/apis/rest-api/v3/): הגרסה המומלצת של REST API למוצרים ולהזמנות.
- [WooCommerce Products API](https://developer.woocommerce.com/docs/apis/rest-api/v3/products): שדות מוצר, סטטוסים, וריאציות ומבנה משאב.
- [WooCommerce Orders API](https://developer.woocommerce.com/docs/apis/rest-api/v3/orders/): יצירה ועדכון של הזמנות, שורות, סכומים וסטטוסים.
- [WooCommerce Webhooks](https://developer.woocommerce.com/docs/apis/rest-api/v3/webhooks/): topics, חתימת HMAC-SHA256, מזהי מסירה ולוגים.
- [WordPress REST API Authentication](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/): הזדהות REST ו-Application Passwords מעל HTTPS.
- [WordPress Application Passwords](https://developer.wordpress.org/advanced-administration/security/application-passwords/): יצירה, שימוש, ניטור וביטול של credentials ייעודיים.

### תקני מעטפת וזמן

- [CloudEvents specification](https://github.com/cloudevents/spec/blob/main/cloudevents/spec.md): מעטפת אירועים ניטרלית ובלתי תלויה בתעבורה.
- [CloudEvents JSON format](https://github.com/cloudevents/spec/blob/main/cloudevents/formats/json-format.md): ייצוג JSON מובנה למעטפת אירוע.
- [RFC 3339](https://www.rfc-editor.org/rfc/rfc3339): פורמט timestamp חד-משמעי.

## 19. תוצאה נדרשת מהשלב הבא

תשתית הקריאה הניטרלית של Complete99 כבר קיימת. לאחר קבלת החבילה הרשמית מ-MyShop, מעדכנים את החוזה, המרשם ושכבת המתאם עם:

1. מפת שדות מאושרת בין WooCommerce ל-MyShop.
2. מפת סטטוסים מאושרת.
3. שיטת auth וחתימה מאושרת.
4. מגבלות מספריות מאושרות.
5. transport binding מאושר לכל הודעה שהספק אכן תומך בה.
6. fixtures אמיתיים שעברו redaction.
7. תוצאות sandbox ו-UAT.

לאחר מכן, ורק לאחר מעבר בדיקות הקבלה, משנים את המחבר מ-`contract_required/unbound` ל-`bound` עם transport מאומת, מפעילים את שני הערוצים, מקצים credential נגזר ומסובב, משנים את הצרכן ל-`active`, ומאשרים `channel_offers` שמצביעות למוצרי WooCommerce חיים. עד אז ה-endpoint נשאר חסום לצרכן MyShop ואין להמציא endpoint, credential, payload ספק או טענת חיבור חי.
