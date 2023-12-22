<?php

namespace App\Repositories\Payroll\Payslip;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Payslip;
use App\Models\PayslipRemark;

use Illuminate\Support\Facades\DB;
use App\Models\FestivalBonusPolicy;
use App\Models\UserPayrollPolicyMap;
use App\Services\Constant\SalaryTypeService;
use App\Services\Constant\AmountValueTypeService;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use App\Services\Constant\PayrollPolicyCategoryService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PayslipRepository
{
    private Payslip $model;
    private PayslipRemark $payslipRemarkModel;
    private User $userModel;
    private UserPayrollPolicyMap $userPayrollPolicyMapModel;
    private FestivalBonusPolicy $festivalBonusPolicyModel;

    public function __construct(Payslip $model, PayslipRemark $payslipRemarkModel, User $userModel, UserPayrollPolicyMap $userPayrollPolicyMapModel, FestivalBonusPolicy $festivalBonusPolicyModel)
    {
        $this->model = $model;
        $this->payslipRemarkModel = $payslipRemarkModel;
        $this->userModel = $userModel;
        $this->userPayrollPolicyMapModel = $userPayrollPolicyMapModel;
        $this->festivalBonusPolicyModel = $festivalBonusPolicyModel;
    }

    public function index($show, $sort, $search)
    {
        $query  = $this->model->query();

        return $query->paginate($show);
    }

    public function all()
    {
        return $this->model->all();
    }

    public function findById($id)
    {
        try {
            return $this->model->findOrFail($id);
        } catch (\Throwable $th) {
            logExceptionInSlack($th);
            throw new NotFoundHttpException('Not Found');
        }
    }

    public function store($validated)
    {
        try {
            return  $this->model->create([
                ...$validated

            ]);
        } catch (\Throwable $th) {
            // logExceptionInSlack($th);
            info($th);
            throw new StoreResourceFailedException('Create Failed');
        }
    }

    public function update($id, $validated)
    {
        try {
            $model = $this->findById($id);
        } catch (\Throwable $th) {
            logExceptionInSlack($th);
            throw new NotFoundHttpException('Not Found');
        }



        try {
            return  DB::transaction(function () use ($validated, $model) {
                $payslip = $model->update([

                    'gross_salary' => $validated['gross_salary'],
                    'over_time_amount' => $validated['over_time_amount'],
                    'projects' => $validated['projects'],
                    'kpi_commission_amount' => $validated['kpi_commission_amount'],
                    'festival_bonus_policies' => $validated['festival_bonus_policies'],
                    'festival_bonus_policy_amount' => $validated['festival_bonus_policy_amount'],
                    'extra_leave_day_count' => $validated['extra_leave_day_count'],
                    'extra_leave_fine_amount' => $validated['extra_leave_fine_amount'],
                    'delay_fine_amount' => $validated['delay_fine_amount'],
                    'general_fine_amount' => $validated['general_fine_amount'],
                    'loan_installment_amount' => $validated['loan_installment_amount'],
                    'insurance_amount' => $validated['insurance_amount'],
                    'total_payable_amount' => $validated['total_payable_amount'],
                    'project_commission_amount' => $validated['project_commission_amount'],
                    'income_tax_amount' => $validated['income_tax_amount'],
                    'account_id' => $validated['account_id'],
                    'user_id' => $validated['user_id'],
                    'income_tax_policy_id' => $validated['income_tax_policy_id'],
                    'salary_policy_id' => $validated['salary_policy_id'],
                    'project_commission_policy_id' => $validated['project_commission_policy_id'],
                    'over_time_policy_id' => $validated['over_time_policy_id'],
                    'delay_fine_policy_id' => $validated['delay_fine_policy_id'],
                    'loan_id' => $validated['loan_id'],
                    'insurance_policy_id' => $validated['insurance_policy_id'],
                    'gross_salary_remarks' => $validated['gross_salary_remarks'],
                    'salary_policy_remarks' => $validated['salary_policy_remarks'],
                    'project_commission_remarks' => $validated['project_commission_remarks'],
                    'kpi_commission_remarks' => $validated['kpi_commission_remarks'],
                    'general_fine_remarks' => $validated['general_fine_remarks'],
                    'insurance_remarks' => $validated['insurance_remarks'],
                    'over_time_remarks' => $validated['over_time_remarks'],
                    ['project_commission_remarks']
                ]);


                $this->payslipRemarkModel->create(
                    [
                        'gross_salary' => $validated['gross_salary'],
                        'gross_salary_remarks' => $validated['gross_salary'],
                        'salary_policy_remarks' => $validated['gross_salary'],
                        'project_commission_amount' => $validated['project_commission_amount'],
                        'project_commission_remarks' => $validated['project_commission_remarks'],
                        'insurance_amount' => $validated['insurance_amount'],
                        'income_tax_amount' => $validated['income_tax_amount'],
                        'insurance_remarks' => $validated['insurance_remarks'],
                        'over_time_amount' => $validated['over_time_amount'],
                        'over_time_remarks' => $validated['over_time_remarks'],
                        'income_tax_remarks' => $validated['income_tax_remarks'],
                        'payslip_id' => $model->id,
                        'user_id' => $model->user_id,
                        'project_commission_policy_id' => $validated['project_commission_policy_id'],
                        'insurance_policy_id' => $validated['insurance_policy_id'],
                        'over_time_policy_id' => $validated['over_time_policy_id'],
                        'income_tax_policy_id' => $validated['income_tax_policy_id'],

                    ]
                );

                return $model->fresh();
            });
        } catch (\Throwable $th) {
            // logExceptionInSlack($th);

            info($th);
            throw new UpdateResourceFailedException('Update Failed');
        }
    }

    public function delete($id)
    {
        try {
            $data = $this->findById($id);
        } catch (\Throwable $th) {
            logExceptionInSlack($th);
            throw new NotFoundHttpException('Not Found');
        }

        try {
            $data->delete();
        } catch (\Throwable $th) {
            logExceptionInSlack($th);
            throw new DeleteResourceFailedException('Delete Failed');
        }
    }

    public function calculateSalaryPolicy($userId)
    {

        try {
            $user = $this->userModel->findOrFail($userId);
        } catch (\Throwable $th) {
            logExceptionInSlack($th);
            throw new NotFoundHttpException('User Not Found');
        }

        try {
            $salaryPolicyData = $this->userPayrollPolicyMapModel->select('salary_policies.*')
                ->join('salary_policies', 'user_payroll_policy_maps.policy_id', '=', 'salary_policies.id')
                ->where(
                    [
                        'user_payroll_policy_maps.user_id' => $user->id,
                        'user_payroll_policy_maps.policy_category_id' => PayrollPolicyCategoryService::SALARY_POLICY
                    ]
                )->first();
        } catch (\Throwable $th) {
            // logExceptionInSlack($th);
            throw new NotFoundHttpException('Salary Policy Data Fetch Error');
        }

        try {
            return DB::transaction(function () use ($user, $salaryPolicyData) {

                info($salaryPolicyData);
                $salary = $user->salary;
                $basic = $salaryPolicyData->basic;
                $basicSalary = $salary * ($basic / 100);
                $now = Carbon::now();
                $existingPayslip = $this->model
                    ->where('user_id', $user->id)
                    ->whereYear('created_at', $now->year)
                    ->whereMonth('created_at', $now->month)
                    ->first();


                if ($existingPayslip) {
                    return  $existingPayslip;
                } else {

                    $payslip = $this->model->create([
                        'basic_salary' => $basicSalary,
                        'salary_policy_id' => $salaryPolicyData->id,
                        'user_id' => $user->id
                    ]);
                    $this->payslipRemarkModel->create([
                        'basic_salary' => $basicSalary,
                        'payslip_id' =>  $payslip->id,
                        'user_id' => $user->id,
                        'salary_policy_id' => $salaryPolicyData->id
                    ]);

                    return $payslip;
                }
            });
        } catch (\Throwable $th) {
            info($th);
            throw new NotFoundHttpException('Salary Policy Calculate Error');
        }
    }


    public function updateSalaryPolicyCalculation($userId, $validated)
    {

        try {
            $user = $this->userModel->findOrFail($userId);
        } catch (\Throwable $th) {
            logExceptionInSlack($th);
            throw new NotFoundHttpException('User Not Found');
        }

        try {
            return DB::transaction(function () use ($user,  $validated) {

                $this->userPayrollPolicyMapModel->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'policy_category_id' => PayrollPolicyCategoryService::SALARY_POLICY,
                    ],
                    [
                        'policy_id' => $validated['salary_policy_id'],
                    ]
                );

                $salaryPolicyData = $this->userPayrollPolicyMapModel->select('salary_policies.*')
                    ->join('salary_policies', 'user_payroll_policy_maps.policy_id', '=', 'salary_policies.id')
                    ->where(
                        [
                            'user_payroll_policy_maps.user_id' => $user->id,
                            'user_payroll_policy_maps.policy_category_id' => PayrollPolicyCategoryService::SALARY_POLICY
                        ]
                    )->first();

                $salary = $user->salary;
                $basic = $salaryPolicyData->basic;
                $basicSalary = $salary * ($basic / 100);

                $payslipData = $this->model->findOrFail($validated['payslip_id']);

                $payslipData->update([
                    'basic_salary' => $basicSalary,
                    'salary_policy_id' => $salaryPolicyData->id
                ]);

                $this->payslipRemarkModel->create([
                    'basic_salary' => $basicSalary,
                    'payslip_id' => $validated['payslip_id'],
                    'user_id' => $user->id,
                    'salary_policy_id' => $salaryPolicyData->id,
                    'salary_policy_remarks' => $validated['remarks']


                ]);

                return $payslipData->fresh();
            });
        } catch (\Throwable $th) {
            info($th);
            throw new NotFoundHttpException('Salary Policy Calculation Update Error');
        }
    }

    public function updateFestivalBonusPolicyCalculation($userId, $validated)
    {
        try {
            $user = $this->userModel->findOrFail($userId);
        } catch (\Throwable $th) {
            logExceptionInSlack($th);
            throw new NotFoundHttpException('User Not Found');
        }

        try {
            return DB::transaction(function () use ($user,  $validated) {

                $payslipData = $this->model->findOrFail($validated['payslip_id']);
                $totalFestivalBonusAmount = 0;

                foreach ($validated['festival_bonus_policies'] as $item) {

                    $festivalBonusData = $this->festivalBonusPolicyModel->findOrFail($item);

                    if ($festivalBonusData->amount_value_type == AmountValueTypeService::FLAT) {
                        $totalFestivalBonusAmount += $festivalBonusData->amount_value;
                    } elseif (
                        $festivalBonusData->amount_value_type == AmountValueTypeService::PERCENTAGE &&
                        $festivalBonusData->salary_type == SalaryTypeService::GROSS
                    ) {
                        $totalFestivalBonusAmount += ($festivalBonusData->amount_value * $user->salary) / 100;
                    } elseif (
                        $festivalBonusData->amount_value_type == AmountValueTypeService::PERCENTAGE &&
                        $festivalBonusData->salary_type == SalaryTypeService::BASIC
                    ) {
                        $totalFestivalBonusAmount += ($festivalBonusData->amount_value * $payslipData->basic_salary) / 100;
                    }
                }

                $payslipData->update([
                    'festival_bonus_policies' => $validated['festival_bonus_policies'],
                    'festival_bonus_policy_amount' => $totalFestivalBonusAmount,
                ]);

                return $payslipData->fresh();
            });
        } catch (\Throwable $th) {
            info($th);
            throw new NotFoundHttpException('Festival Bonus Calculation Update Error');
        }
    }

    public function updateInsurancePolicyCalculation($userId, $validated)
    {
        try {
            $user = $this->userModel->findOrFail($userId);
        } catch (\Throwable $th) {
            logExceptionInSlack($th);
            throw new NotFoundHttpException('User Not Found');
        }

        try {
            return DB::transaction(function () use ($user,  $validated) {

                $this->userPayrollPolicyMapModel->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'policy_category_id' => PayrollPolicyCategoryService::INSURANCE_POLICY,
                    ],
                    [
                        'policy_id' => $validated['insurance_policy_id'],
                    ]
                );

                $insurancePolicyData = $this->userPayrollPolicyMapModel->select('insurance_policies.*')
                    ->join('insurance_policies', 'user_payroll_policy_maps.policy_id', '=', 'insurance_policies.id')
                    ->where(
                        [
                            'user_payroll_policy_maps.user_id' => $user->id,
                            'user_payroll_policy_maps.policy_category_id' => PayrollPolicyCategoryService::INSURANCE_POLICY
                        ]
                    )->first();

                $payslipData = $this->model->findOrFail($validated['payslip_id']);

                if ($insurancePolicyData->amount_value_type == AmountValueTypeService::FLAT) {
                    $calculatedInsuranceAmount = $insurancePolicyData->amount_value;
                } elseif (
                    $insurancePolicyData->amount_value_type == AmountValueTypeService::PERCENTAGE &&
                    $insurancePolicyData->salary_type == SalaryTypeService::GROSS
                ) {
                    $calculatedInsuranceAmount = ($insurancePolicyData->amount_value * $user->salary) / 100;
                } elseif (
                    $insurancePolicyData->amount_value_type == AmountValueTypeService::PERCENTAGE &&
                    $insurancePolicyData->salary_type == SalaryTypeService::BASIC
                ) {
                    $calculatedInsuranceAmount = ($insurancePolicyData->amount_value * $payslipData->basic_salary) / 100;
                }

                $payslipData->update([
                    'insurance_amount' => $calculatedInsuranceAmount,
                    'insurance_policy_id' => $insurancePolicyData->id
                ]);

                $this->payslipRemarkModel->create([
                    'insurance_amount' => $calculatedInsuranceAmount,
                    'payslip_id' => $validated['payslip_id'],
                    'user_id' => $user->id,
                    'insurance_policy_id' => $insurancePolicyData->id,
                    'insurance_remarks' => $validated['remarks']
                ]);

                return $payslipData->fresh();
            });
        } catch (\Throwable $th) {
            info($th);
            throw new NotFoundHttpException('Insurance Policy Calculation Update Error');
        }
    }
}
