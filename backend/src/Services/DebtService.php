<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;
use App\Core\Validator;
use App\Repositories\DebtRepository;

final class DebtService
{
    public function __construct(private readonly DebtRepository $repository) {}

    public function list(string $status): array
    {
        if (!in_array($status, ['all', 'open', 'paid'], true)) {
            throw new HttpException(422, 'Filtro inválido.', ['status' => 'Use all, open ou paid.']);
        }
        return array_map([$this, 'present'], $this->repository->all($status));
    }

    public function create(array $input): array
    {
        return $this->present($this->repository->create($this->validate($input)));
    }

    public function update(int $id, array $input): array
    {
        $current = $this->repository->find($id);
        if ($current === null) throw new HttpException(404, 'Dívida não encontrada.');
        return $this->present($this->repository->update($id, $this->validate(array_merge($current, $input))));
    }

    public function payInstallment(int $id): array
    {
        $current = $this->repository->find($id);
        if ($current === null) throw new HttpException(404, 'Dívida não encontrada.');
        if ((int) $current['parcelas_pagas'] >= (int) $current['total_parcelas'] || (float) $current['valor_pago'] >= (float) $current['valor_total']) {
            throw new HttpException(422, 'Esta dívida já está quitada.');
        }

        $installmentValue = (float) $current['valor_total'] / (int) $current['total_parcelas'];
        $current['parcelas_pagas'] = (int) $current['parcelas_pagas'] + 1;
        $current['valor_pago'] = $current['parcelas_pagas'] >= (int) $current['total_parcelas']
            ? (float) $current['valor_total']
            : min((float) $current['valor_total'], round((float) $current['valor_pago'] + $installmentValue, 2));
        return $this->present($this->repository->update($id, $this->validate($current)));
    }

    public function delete(int $id): void
    {
        if (!$this->repository->delete($id)) throw new HttpException(404, 'Dívida não encontrada.');
    }

    private function validate(array $input): array
    {
        $name = trim((string) ($input['nome'] ?? ''));
        $creditor = trim((string) ($input['credor'] ?? ''));
        $note = trim((string) ($input['observacao'] ?? ''));
        $total = filter_var($input['total_parcelas'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 600]]);
        $paidInstallments = filter_var($input['parcelas_pagas'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 600]]);
        $dueDay = ($input['dia_vencimento'] ?? null) === '' ? null : ($input['dia_vencimento'] ?? null);
        $errors = [];
        if ($name === '' || mb_strlen($name) > 150) $errors['nome'] = 'Informe um nome com até 150 caracteres.';
        if ($creditor !== '' && mb_strlen($creditor) > 150) $errors['credor'] = 'Informe um credor com até 150 caracteres.';
        if (!Validator::positiveNumber($input['valor_total'] ?? null)) $errors['valor_total'] = 'Informe um valor total maior que zero.';
        if ($total === false) $errors['total_parcelas'] = 'Informe entre 1 e 600 parcelas.';
        if ($paidInstallments === false || ($total !== false && $paidInstallments > $total)) $errors['parcelas_pagas'] = 'As parcelas pagas não podem superar o total.';
        if (!Validator::positiveNumber($input['valor_pago'] ?? 0, true) || (float) ($input['valor_pago'] ?? 0) > (float) ($input['valor_total'] ?? 0)) $errors['valor_pago'] = 'O valor pago deve ficar entre zero e o valor total.';
        if ($dueDay !== null && filter_var($dueDay, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 31]]) === false) $errors['dia_vencimento'] = 'Informe um dia entre 1 e 31.';
        Validator::throwIf($errors);

        return [
            'nome' => $name,
            'credor' => $creditor === '' ? null : $creditor,
            'valor_total' => round((float) $input['valor_total'], 2),
            'total_parcelas' => (int) $total,
            'parcelas_pagas' => (int) $paidInstallments,
            'valor_pago' => round((float) ($input['valor_pago'] ?? 0), 2),
            'dia_vencimento' => $dueDay === null ? null : (int) $dueDay,
            'observacao' => $note === '' ? null : $note,
        ];
    }

    private function present(array $row): array
    {
        $total = (float) $row['valor_total'];
        $paid = (float) $row['valor_pago'];
        $row['saldo_devedor'] = round(max(0, $total - $paid), 2);
        $row['valor_parcela'] = round($total / max(1, (int) $row['total_parcelas']), 2);
        $row['percentual_pago'] = $total > 0 ? round(min(100, $paid / $total * 100), 1) : 0;
        $row['situacao'] = $row['saldo_devedor'] <= 0 || (int) $row['parcelas_pagas'] >= (int) $row['total_parcelas'] ? 'quitada' : 'aberta';
        return $row;
    }
}
