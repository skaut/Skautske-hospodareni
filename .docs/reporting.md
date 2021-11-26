
## report_chit_items
```sql
SELECT `ch`.`id` AS `chit_id`,`ch`.`eventId` AS `eventId`,`ch`.`recipient` AS `recipient`,`ch`.`num` AS `num`,`ch`.`date` AS `date`,
`ch`.`payment_method` AS `payment_method`,`ci`.`id` AS `id`,`ci`.`purpose` AS `purpose`,
`ci`.`price` AS `price`,`ci`.`priceText` AS `priceText`,`ci`.`category` AS `category`,`ci`.`category_operation_type` AS `category_operation_type`
FROM `ac_chits` `ch`
LEFT JOIN `ac_chit_to_item` `cti` ON `ch`.`id` = `cti`.`chit_id`
LEFT JOIN `ac_chits_item` `ci` ON `cti`.`item_id` = `ci`.`id`;
```

## report_cashbooks
```sql
SELECT c.type as Type,
        YEAR(`ch`.`date`) AS `Year`,
        COUNT(DISTINCT `ch`.`eventId`) AS `count of cashbooks`,
        ROUND(SUM(`ci`.`price` )) AS `Total amount`
FROM `ac_chits` `ch` 
LEFT JOIN `ac_chit_to_item` `cti` ON `ch`.`id` = `cti`.`chit_id` 
LEFT JOIN `ac_chits_item` `ci` ON `cti`.`item_id` = `ci`.`id`
LEFT JOIN `ac_cashbook` `c` ON `ch`.`eventId` = `c`.`id`
GROUP BY Type, Year
ORDER BY Type, Year DESC 
```

## report_cashbooks_amount
```sql
SELECT c.type as Type,
        YEAR(`ch`.`date`) AS `Year`,
        `ci`.`category_operation_type` as `Operational type`,
        ROUND(SUM(`ci`.`price` )) AS `Amount`
FROM `ac_chits` `ch` 
LEFT JOIN `ac_chit_to_item` `cti` ON `ch`.`id` = `cti`.`chit_id` 
LEFT JOIN `ac_chits_item` `ci` ON `cti`.`item_id` = `ci`.`id`
LEFT JOIN `ac_cashbook` `c` ON `ch`.`eventId` = `c`.`id`
GROUP BY Type, Year, `ci`.`category_operation_type`
ORDER BY Type, Year DESC
```

## report_payment_groups
```sql
SELECT g.groupType AS Type,
    YEAR(p.due_date) AS Year, 
    COUNT(DISTINCT g.id) AS 'Count of groups'
FROM `pa_payment` p
LEFT JOIN pa_group g ON g.id = p.group_id
WHERE  p.state != 'canceled'
GROUP BY Year, Type
ORDER BY Type, Year DESC
```

## report_payment_groups_amounts
```sql
SELECT g.groupType AS Type,
    YEAR(p.due_date) AS Year, 
    p.state AS 'Status',
    ROUND(SUM(p.amount)) AS 'Total amount'
FROM `pa_payment` p
LEFT JOIN pa_group g ON g.id = p.group_id
GROUP BY Year, Type, p.state
ORDER BY Type, Year DESC
```
