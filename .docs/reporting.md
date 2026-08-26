# Dotazy pro přehledy

Tyto dotazy vytvářejí jednorázové přehledy z databáze a data nemění. Spouštějte je jen s oprávněním ke čtení, nejdříve je vyzkoušejte na kopii dat a při exportu dodržujte pravidla pro práci s osobními údaji.

Příklady používají proměnné MySQL. Před spuštěním vždy doplňte vlastní hodnotu a zkontrolujte, jaká data dotaz vrátí.

## Položky dokladů konkrétní pokladní knihy

```sql
SET @cashbook_id = 'doplňte-ID-pokladní-knihy';

SELECT
    ch.id AS chit_id,
    ch.eventId AS cashbook_id,
    ch.recipient,
    ch.num,
    ch.date,
    ch.payment_method,
    ci.id AS item_id,
    ci.purpose,
    ci.price,
    ci.priceText,
    ci.category,
    ci.category_operation_type
FROM ac_chits AS ch
LEFT JOIN ac_chit_to_item AS cti ON ch.id = cti.chit_id
LEFT JOIN ac_chits_item AS ci ON cti.item_id = ci.id
WHERE ch.eventId = @cashbook_id
ORDER BY ch.date, ch.num, ci.id;
```

## Počet pokladních knih podle typu a roku

```sql
SELECT
    c.type AS cashbook_type,
    YEAR(ch.date) AS year,
    COUNT(DISTINCT ch.eventId) AS cashbook_count,
    ROUND(SUM(ci.price)) AS total_amount
FROM ac_chits AS ch
LEFT JOIN ac_chit_to_item AS cti ON ch.id = cti.chit_id
LEFT JOIN ac_chits_item AS ci ON cti.item_id = ci.id
LEFT JOIN ac_cashbook AS c ON ch.eventId = c.id
GROUP BY c.type, YEAR(ch.date)
ORDER BY c.type, YEAR(ch.date) DESC;
```

## Částky pokladních knih podle typu, roku a operace

```sql
SELECT
    c.type AS cashbook_type,
    YEAR(ch.date) AS year,
    ci.category_operation_type AS operation_type,
    ROUND(SUM(ci.price)) AS total_amount
FROM ac_chits AS ch
LEFT JOIN ac_chit_to_item AS cti ON ch.id = cti.chit_id
LEFT JOIN ac_chits_item AS ci ON cti.item_id = ci.id
LEFT JOIN ac_cashbook AS c ON ch.eventId = c.id
GROUP BY c.type, YEAR(ch.date), ci.category_operation_type
ORDER BY c.type, YEAR(ch.date) DESC, ci.category_operation_type;
```

## Platební skupiny podle typu a roku splatnosti

```sql
SELECT
    g.groupType AS group_type,
    YEAR(p.due_date) AS year,
    COUNT(DISTINCT g.id) AS group_count
FROM pa_payment AS p
LEFT JOIN pa_group AS g ON g.id = p.group_id
WHERE p.state != 'canceled'
GROUP BY g.groupType, YEAR(p.due_date)
ORDER BY g.groupType, YEAR(p.due_date) DESC;
```

## Částky plateb podle typu skupiny, roku a stavu

```sql
SELECT
    g.groupType AS group_type,
    YEAR(p.due_date) AS year,
    p.state,
    ROUND(SUM(p.amount)) AS total_amount
FROM pa_payment AS p
LEFT JOIN pa_group AS g ON g.id = p.group_id
GROUP BY g.groupType, YEAR(p.due_date), p.state
ORDER BY g.groupType, YEAR(p.due_date) DESC, p.state;
```
