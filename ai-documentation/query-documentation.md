# Zume Church Queries Documentation

## Overview
The `Zume_Query_Churches` class provides database query methods for retrieving church/group data from the Zume coaching system. All queries target the WordPress-based database structure with custom tables for location and metadata.

## Database Schema Context
The queries work with the following key tables:
- `zume_posts` - Main posts table containing church/group records
- `zume_postmeta` - Metadata table for additional post properties
- `zume_dt_location_grid_meta` - Location grid metadata for geographic data

## Query Methods

### 1. `churches_with_location()`
**Purpose**: Retrieves all churches/groups that have location data associated with them.

**Parameters**: None

**Return Value**: 
- Array of associative arrays containing church data
- Empty array if no results found

**Return Structure**:
```php
[
    [
        'post_id' => int,      // WordPress post ID
        'name' => string,      // Church/group name (post_title)
        'post_type' => 'groups', // Always 'groups'
        'grid_id' => int,      // Location grid ID
        'lng' => float,        // Longitude
        'lat' => float,        // Latitude
        'level' => string,     // Location level/precision
        'source' => string,    // Location data source
        'label' => string      // Location label/description
    ]
]
```

**Query Strategy**: 
- LEFT JOINs postmeta and location grid tables
- Filters for post_type = 'groups'
- Only returns records with location metadata

---

### 2. `churches_by_boundary(float $north, float $south, float $east, float $west)`
**Purpose**: Retrieves churches/groups within a specific geographic bounding box.

**Parameters**:
- `$north` (float): Northern latitude boundary
- `$south` (float): Southern latitude boundary  
- `$east` (float): Eastern longitude boundary
- `$west` (float): Western longitude boundary

**Return Value**: 
- Array of associative arrays (same structure as `churches_with_location()`)
- Empty array if no results found

**Query Strategy**:
- Same base query as `churches_with_location()`
- Adds geographic boundary conditions:
  - `lgm.lat > $south AND lgm.lat < $north`
  - `lgm.lng > $west AND lgm.lng < $east`

**Security Note**: Parameters are directly interpolated into SQL - ensure proper validation before calling.

---

### 3. `query_total_churches()`
**Purpose**: Returns the total count of active churches in the system.

**Parameters**: None

**Return Value**: 
- `int` - Total count of active churches
- `0` if no results or query fails

**Query Strategy**:
- JOINs postmeta twice to filter by specific metadata:
  - `group_type = 'church'`
  - `group_status = 'active'`
- Uses COUNT(*) for performance
- Only counts posts with post_type = 'groups'

---

### 4. `query_churches_cumulative(int $range, bool $current = true)`
**Purpose**: Returns cumulative count of churches over a specified time range.

**Parameters**:
- `$range` (int): Number of days to look back
- `$current` (bool): If true, counts up to current time; if false, counts up to $range days ago

**Return Value**:
- `int` - Count of churches within the time range
- `0` if no results found

**Query Strategy**:
- Uses `church_start_date` metadata to filter by time range
- Time boundaries:
  - Begin: Always starts from timestamp 1 (epoch)
  - End: Current time OR (current time - $range days) based on $current parameter
- LEFT JOIN with church_start_date metadata
- Filters for active groups only

**Time Range Logic**:
- If `$current = true`: Count churches from beginning to now
- If `$current = false`: Count churches from beginning to $range days ago

## Common Query Patterns

### Location-Based Queries
- Use LEFT JOIN with `zume_dt_location_grid_meta` via `location_grid_meta` postmeta
- Location data includes coordinates, grid IDs, and hierarchical levels

### Status Filtering
- Active churches: `group_status = 'active'`
- Church type: `group_type = 'church'`
- Use JOIN for required conditions, LEFT JOIN for optional data

### Time-Based Queries
- Use Unix timestamps in metadata values
- `church_start_date` stores creation/start timestamps
- Time range queries use simple comparison operators

## Usage Recommendations

1. **Geographic Queries**: Use `churches_by_boundary()` for map-based features and location filtering
2. **Statistics**: Use `query_total_churches()` for dashboard counts and reporting
3. **Trend Analysis**: Use `query_churches_cumulative()` for growth tracking and historical data
4. **Full Dataset**: Use `churches_with_location()` for comprehensive mapping or export features

## Performance Considerations

- All queries use appropriate JOINs to minimize data transfer
- Location queries include lat/lng indexes for geographic filtering
- Count queries use COUNT(*) instead of SELECT * for better performance
- Consider caching results for frequently accessed statistics

## Integration Notes

- All methods are static and can be called directly: `Zume_Query_Churches::method_name()`
- Compatible with WordPress database abstraction layer ($wpdb)
- Results are returned as associative arrays for easy JSON serialization
- Geographic data follows standard lat/lng conventions (WGS84)
