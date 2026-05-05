# Are We Even?

**Are We Even?** is a simple yet powerful expense tracking application designed to help friends, roommates, or partners keep track of shared costs and settle balances effortlessly.

## How it Works

1.  **Register & Connect**: Create an account and invite a friend via email.
2.  **Form a Relation**: Once your friend accepts the invitation, a "Relation" is established between the two of you.
3.  **Track Expenses**: Log shared expenses within that relation. You specify who paid, and the app automatically calculates the balance.
4.  **Stay Balanced**: The dashboard shows exactly who owes whom and how much, so you can stay "even."

## Key Features

-   **User Authentication**: Secure registration, login, and password reset.
-   **Invitation System**: Seamlessly connect with others via email invites.
-   **Expense Management**: Quick entry for shared costs with descriptions and timestamps.
-   **Live Balances**: Real-time calculation of debts within each relation.
-   **Mobile Friendly**: Clean, flat-UI design optimized for both desktop and mobile use.

## Technical Stack

-   **Backend**: [Symfony 7.4](https://symfony.com/) (PHP 8.2+)
-   **Database**: [PostgreSQL](https://www.postgresql.org/)
-   **Frontend**: Twig templates with vanilla CSS.
-   **Infrastructure**: Dockerized for easy local development and deployment.

## Getting Started

### Prerequisites

-   Docker and Docker Compose installed.

### Local Development

1.  Clone the repository.
2.  Create a `.env.local` file and configure your `DATABASE_URL` (or use the default Docker configuration).
3.  Start the containers:
    ```bash
    docker compose up -d
    ```
4.  Install dependencies:
    ```bash
    docker compose exec php composer install
    ```
5.  Run migrations:
    ```bash
    docker compose exec php bin/console doctrine:migrations:migrate
    ```
6.  Access the app at `http://localhost`.
