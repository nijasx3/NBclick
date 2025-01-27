<?php

namespace App\Entity;

use App\Repository\BetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BetRepository::class)]
class Bet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?string $id_match = null;

    #[ORM\Column(length: 50)]
    private ?string $bet_bet = null;

    #[ORM\Column]
    private ?int $price_bet = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2)]
    private ?string $odds_bet = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $result_bet = null;

    #[ORM\ManyToOne(inversedBy: 'bets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $id_user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdMatch(): ?string
    {
        return $this->id_match;
    }

    public function setIdMatch(string $id_match): static
    {
        $this->id_match = $id_match;

        return $this;
    }

    public function getBetBet(): ?string
    {
        return $this->bet_bet;
    }

    public function setBetBet(string $bet_bet): static
    {
        $this->bet_bet = $bet_bet;

        return $this;
    }

    public function getPriceBet(): ?int
    {
        return $this->price_bet;
    }

    public function setPriceBet(int $price_bet): static
    {
        $this->price_bet = $price_bet;

        return $this;
    }

    public function getOddsBet(): ?string
    {
        return $this->odds_bet;
    }

    public function setOddsBet(string $odds_bet): static
    {
        $this->odds_bet = $odds_bet;

        return $this;
    }

    public function getResultBet(): ?string
    {
        return $this->result_bet;
    }

    public function setResultBet(?string $result_bet): static
    {
        $this->result_bet = $result_bet;

        return $this;
    }

    public function getIdUser(): ?User
    {
        return $this->id_user;
    }

    public function setIdUser(?User $id_user): static
    {
        $this->id_user = $id_user;

        return $this;
    }
}
