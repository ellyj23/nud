# Database Migration Instructions

## Adding created_at Column to Clients Table

Before using the new UX features (time-ago counter and 24-hour JOSEPH filter), you need to run the database migration to add the `created_at` column to the `clients` table.

### Steps to Run Migration

1. **Via Command Line** (recommended):
   ```bash
   cd /path/to/duns
   php migrate_add_created_at.php
   ```

2. **Via Browser**:
   - Navigate to: `http://your-domain.com/migrate_add_created_at.php`
   - You should see: "Migration completed successfully!"

### What the Migration Does

1. Checks if `created_at` column already exists
2. Adds `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP` column to the `clients` table
3. Updates existing records to use their `date` field as `created_at`

### Verification

After running the migration, verify it worked:

```sql
DESCRIBE clients;
```

You should see a `created_at` column with type `timestamp`.

### Rollback (if needed)

If you need to remove the column:

```sql
ALTER TABLE clients DROP COLUMN created_at;
```

## New Features After Migration

Once the migration is complete, the following features will be active:

1. **Time-Ago Counter**: Each date in the table will show a relative time (e.g., "2 days ago") below the actual date
2. **24-Hour JOSEPH Filter**: Clients with "JOSEPH" in the Responsible field will be hidden from search/filter results for 24 hours after creation
3. **Duplicate Reg No Validation**: Already active - prevents duplicate registration numbers

## Notes

- The migration is safe to run multiple times - it checks if the column exists first
- Existing client records will have their `created_at` set to their `date` field value
- New clients will automatically get the current timestamp when inserted
