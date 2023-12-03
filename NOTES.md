# SQL poznámky

## položky dokladů

```sql
SELECT i.id, i.purpose
FROM `ac_chitsView` c
LEFT JOIN `ac_chit_to_item` ci ON c.`chit_id` = ci.`chit_id`
LEFT JOIN `ac_chits_item` i ON ci.`item_id` = i.`id`
WHERE c.`eventId` = '23f8ff7f-f882-4a28-b699-3021720f119a'
```

