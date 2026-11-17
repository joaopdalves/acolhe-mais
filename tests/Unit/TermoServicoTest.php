<?php
declare(strict_types=1);

namespace Acolhe\Testes\Unit;

use Acolhe\Dominio\RegraDeNegocioException;
use Acolhe\Servico\TermoServico;
use Acolhe\Testes\Fakes\TermosEmMemoria;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Fakes/RepositoriosEmMemoria.php';

final class TermoServicoTest extends TestCase
{
    private TermosEmMemoria $repo;
    private TermoServico $servico;
    private array $paulo = ['id_usuario' => 5, 'id_organizacao' => 1];

    protected function setUp(): void
    {
        $termo = static fn (int $id, bool $obrigatorio = true, bool $vigente = true) =>
            ['id_termo' => $id, 'id_organizacao' => 1, 'titulo' => "Termo {$id}", 'versao' => 1, 'obrigatorio' => $obrigatorio, 'vigente' => $vigente];
        $this->repo = new TermosEmMemoria([$termo(1), $termo(2), $termo(3), $termo(4), $termo(5, false), $termo(6, true, false)]);
        $this->servico = new TermoServico($this->repo);
    }

    public function testListaSoOsTermosVigentesEObrigatorios(): void
    {
        self::assertSame([1, 2, 3, 4], array_column($this->servico->pendentes($this->paulo), 'id_termo'));
    }

    public function testNaoLiberaATrilhaSemTodosOsTermosMarcados(): void
    {
        try {
            $this->servico->aceitarTodos($this->paulo, [1, 2, 3], '10.0.0.1', new DateTimeImmutable());
            self::fail('deveria exigir os 4 termos');
        } catch (RegraDeNegocioException $e) {
            self::assertSame('Marque todos os termos para continuar. Falta 1.', $e->getMessage());
        }
        self::assertSame([], $this->repo->aceites, 'nenhum aceite parcial é gravado');
    }

    public function testAceiteRegistraCadaTermoComOIp(): void
    {
        $n = $this->servico->aceitarTodos($this->paulo, ['1', '2', '3', '4'], '189.45.67.12', new DateTimeImmutable());
        self::assertSame(4, $n);
        self::assertSame([1, 5, '189.45.67.12'], $this->repo->aceites[0]);
        self::assertFalse($this->servico->temPendencia($this->paulo));
    }
}
