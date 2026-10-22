# Spec Delta

## Purpose

Defines the operational dashboard metrics used by hall leadership and limits house-tutor complaint figures to the tutor's assigned blocks.

## ADDED Requirements

### Requirement: Hall operations metrics
The system SHALL show authorized Hall Office Staff, Provost, and Super Admin users total seats, occupied seats, occupancy percentage, Pending registrations, open complaints by category, overdue complaints, and average resolution time for complaints resolved in the last 30 days.

#### Scenario: Hall leadership views dashboard
- **WHEN** Hall Office Staff or Provost opens the dashboard
- **THEN** every required metric is calculated from current persisted records

#### Scenario: No complaints resolved in the period
- **WHEN** no complaint was resolved in the previous 30 days
- **THEN** average resolution time is displayed as unavailable rather than as a misleading duration

### Requirement: Tutor-scoped complaint metrics
The system MUST show a House Tutor open complaints by category, overdue complaints, and 30-day average resolution time limited to complaints associated with assigned blocks. Complaints without a room, including Attached-student complaints identified only by free-text location, MUST be excluded from these block-scoped metrics.

#### Scenario: Tutor views dashboard
- **WHEN** a House Tutor opens the dashboard
- **THEN** complaint counts, overdue figures, and average resolution time exclude complaints outside that tutor's assigned blocks

#### Scenario: Roomless Attached complaints are excluded
- **WHEN** a House Tutor's dashboard is calculated and an Attached-student complaint has no room association
- **THEN** that complaint is excluded from the tutor's block-scoped complaint metrics

### Requirement: Dashboard authorization
The system SHALL make the operational dashboard available only to documented staff, Provost, House Tutor, and Super Admin roles.

#### Scenario: Student requests the operational dashboard
- **WHEN** a Student without another authorized role requests the dashboard
- **THEN** the system returns a forbidden response

### Requirement: Seeded metric accuracy
The system SHALL calculate dashboard values consistently with the required fictional seed data.

#### Scenario: Reviewer opens seeded dashboard
- **WHEN** the required assessment seed set is loaded
- **THEN** the displayed totals and complaint metrics match the underlying seeded records
