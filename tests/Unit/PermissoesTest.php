<?php
declare(strict_types=1);

namespace Acolhe\Testes\Unit;

use Acolhe\Dominio\Permissoes;
use PHPUnit\Framework\TestCase;

final class PermissoesTest extends TestCase
{
    private const INTEGRANTE = 3;
    private const PADRINHO = 2;
    private const COORDENADOR = 1;

    public function testIntegranteConcluiASuaPropriaTarefa(): void
    {
        self::assertNull(Permissoes::motivoParaNaoConcluir('INTEGRANTE', self::INTEGRANTE, 'INTEGRANTE', false, self::INTEGRANTE, self::PADRINHO));
    }

    public function testIntegranteNaoConcluiTarefaDeOutraPessoa(): void
    {
        self::assertSame(
            'Só o próprio integrante pode concluir esta tarefa.',
            Permissoes::motivoParaNaoConcluir('INTEGRANTE', 99, 'INTEGRANTE', false, self::INTEGRANTE, self::PADRINHO)
        );
    }

    public function testTarefaComValidacaoSoOPadrinhoOuACoordenacaoConcluem(): void
    {
        self::assertSame(
            'Esta tarefa precisa da validação do seu padrinho.',
            Permissoes::motivoParaNaoConcluir('INTEGRANTE', self::INTEGRANTE, 'INTEGRANTE', true, self::INTEGRANTE, self::PADRINHO)
        );
        self::assertNull(Permissoes::motivoParaNaoConcluir('PADRINHO', self::PADRINHO, 'INTEGRANTE', true, self::INTEGRANTE, self::PADRINHO));
        self::assertNull(Permissoes::motivoParaNaoConcluir('COORDENADOR', self::COORDENADOR, 'INTEGRANTE', true, self::INTEGRANTE, self::PADRINHO));
    }

    public function testPadrinhoDeOutraPessoaNaoValida(): void
    {
        self::assertNotNull(Permissoes::motivoParaNaoConcluir('PADRINHO', 50, 'INTEGRANTE', true, self::INTEGRANTE, self::PADRINHO));
    }

    public function testTarefaDaCoordenacaoOIntegranteNaoConclui(): void
    {
        self::assertSame(
            'Esta tarefa é feita pela coordenação.',
            Permissoes::motivoParaNaoConcluir('INTEGRANTE', self::INTEGRANTE, 'COORDENADOR', false, self::INTEGRANTE, self::PADRINHO)
        );
        self::assertNull(Permissoes::motivoParaNaoConcluir('ADMINISTRADOR', 7, 'COORDENADOR', false, self::INTEGRANTE, self::PADRINHO));
    }
}
