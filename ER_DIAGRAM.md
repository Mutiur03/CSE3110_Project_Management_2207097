# ScrumLab ER Diagram

This ER diagram is based on the Laravel migrations in `database/migrations` and the Eloquent relationships in `app/Models`.

```mermaid
erDiagram
    USERS {
        uuid id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        string avatar
        string job_title
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    PASSWORD_RESET_TOKENS {
        string email PK
        string token
        timestamp created_at
    }

    SESSIONS {
        string id PK
        uuid user_id FK
        string ip_address
        text user_agent
        longtext payload
        int last_activity
    }

    PROJECTS {
        uuid id PK
        uuid owner_id FK
        string name
        string key UK
        text description
        string status
        timestamp created_at
        timestamp updated_at
    }

    PROJECT_MEMBERS {
        uuid id PK
        uuid project_id FK
        uuid user_id FK
        string role
        timestamp joined_at
        timestamp created_at
        timestamp updated_at
    }

    TEAMS {
        uuid id PK
        uuid project_id FK
        string name
        text description
        timestamp created_at
        timestamp updated_at
    }

    TEAM_MEMBERS {
        uuid id PK
        uuid team_id FK
        uuid user_id FK
        string role
        timestamp joined_at
        timestamp created_at
        timestamp updated_at
    }

    SPRINTS {
        uuid id PK
        uuid project_id FK
        string name
        text goal
        date start_date
        date end_date
        string status
        timestamp created_at
        timestamp updated_at
    }

    ISSUES {
        uuid id PK
        uuid project_id FK
        uuid team_id FK
        uuid sprint_id FK
        uuid reporter_id FK
        uuid assignee_id FK
        uuid parent_issue_id FK
        string key UK
        string title
        text description
        string type
        string status
        string priority
        smallint story_points
        string severity
        text steps_to_reproduce
        text expected_result
        text actual_result
        string environment
        timestamp created_at
        timestamp updated_at
    }

    COMMENTS {
        uuid id PK
        uuid issue_id FK
        uuid user_id FK
        text body
        timestamp created_at
        timestamp updated_at
    }

    ACTIVITY_LOGS {
        uuid id PK
        uuid project_id FK
        uuid issue_id FK
        uuid user_id FK
        string action
        string subject_type
        uuid subject_id
        json old_values
        json new_values
        timestamp created_at
        timestamp updated_at
    }

    NOTIFICATIONS {
        uuid id PK
        string type
        string notifiable_type
        uuid notifiable_id
        json data
        timestamp read_at
        timestamp created_at
        timestamp updated_at
    }

    USERS ||--o{ PROJECTS : owns
    USERS ||--o{ PROJECT_MEMBERS : joins
    PROJECTS ||--o{ PROJECT_MEMBERS : has
    PROJECTS ||--o{ TEAMS : contains
    USERS ||--o{ TEAM_MEMBERS : joins
    TEAMS ||--o{ TEAM_MEMBERS : has
    PROJECTS ||--o{ SPRINTS : plans
    PROJECTS ||--o{ ISSUES : tracks
    TEAMS |o--o{ ISSUES : works_on
    SPRINTS |o--o{ ISSUES : schedules
    USERS ||--o{ ISSUES : reports
    USERS |o--o{ ISSUES : is_assigned
    ISSUES |o--o{ ISSUES : parent_of
    ISSUES ||--o{ COMMENTS : has
    USERS ||--o{ COMMENTS : writes
    PROJECTS |o--o{ ACTIVITY_LOGS : records
    ISSUES |o--o{ ACTIVITY_LOGS : records
    USERS |o--o{ ACTIVITY_LOGS : performs
    USERS |o--o{ SESSIONS : starts
    USERS |o--o{ NOTIFICATIONS : receives
```

## Notes

- `project_members` has a composite unique key on `project_id` and `user_id`.
- `team_members` has a composite unique key on `team_id` and `user_id`.
- `teams` has a composite unique key on `project_id` and `name`.
- `issues.team_id`, `issues.sprint_id`, `issues.assignee_id`, and `issues.parent_issue_id` are nullable foreign keys.
- `activity_logs.project_id`, `activity_logs.issue_id`, and `activity_logs.user_id` are nullable foreign keys.
- `notifications.notifiable_type` and `notifications.notifiable_id` are Laravel polymorphic notification columns. The application currently uses user notifications through `App\Models\User`.
- `password_reset_tokens.email` is keyed by email and does not have a database-level foreign key to `users.email`.
