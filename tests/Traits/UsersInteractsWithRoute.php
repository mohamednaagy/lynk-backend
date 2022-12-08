<?php

namespace Tests\Traits;

trait UsersInteractsWithRoute
{
    private function assertUsersStatusToPostRoute(string $route, int $status, array $users, array $data = [])
    {
        foreach ($users as $user) {
            $this->actingAs($user)
                ->postJson($route, $data)
                ->assertStatus($status);
        }
    }

    private function assertUsersStatusToGetRoute(string $route, int $status, array $users, array $headers = [])
    {
        foreach ($users as $user) {
            $this->actingAs($user)
                ->getJson($route, $headers)
                ->assertStatus($status);
        }
    }

    private function assertUsersStatusToPutRoute(string $route, int $status, array $users, array $data = [], array $headers = [])
    {
        foreach ($users as $user) {
            $this->actingAs($user)
                ->putJson($route, $data, $headers)
                ->assertStatus($status);
        }
    }
}
