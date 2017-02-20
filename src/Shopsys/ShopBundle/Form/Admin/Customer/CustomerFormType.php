<?php

namespace Shopsys\ShopBundle\Form\Admin\Customer;

use Shopsys\ShopBundle\Form\FormType;
use Shopsys\ShopBundle\Model\Customer\CustomerData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerFormType extends AbstractType
{
    const SCENARIO_CREATE = 'create';
    const SCENARIO_EDIT = 'edit';

    /**
     * @var string
     */
    private $scenario;

    /**
     * @var \Shopsys\ShopBundle\Component\Domain\SelectedDomain
     */
    private $selectedDomain;

    /**
     * @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup[]
     */
    private $pricingGroups;

    /**
     * @var \Shopsys\ShopBundle\Model\Country\Country[]
     */
    private $countries;

    /**
     * @param string $scenario
     * @param \Shopsys\ShopBundle\Model\Country\Country[] $countries
     * @param \Shopsys\ShopBundle\Component\Domain\SelectedDomain $selectedDomain
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup[]|null $pricingGroups
     */
    public function __construct($scenario, array $countries, $selectedDomain = null, $pricingGroups = null)
    {
        $this->scenario = $scenario;
        $this->countries = $countries;
        $this->selectedDomain = $selectedDomain;
        $this->pricingGroups = $pricingGroups;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('userData', new UserFormType($this->scenario, $this->selectedDomain, $this->pricingGroups))
            ->add('billingAddressData', new BillingAddressFormType($this->countries))
            ->add('deliveryAddressData', new DeliveryAddressFormType($this->countries))
            ->add('save', FormType::SUBMIT);

        if ($this->scenario === self::SCENARIO_CREATE) {
            $builder->add('sendRegistrationMail', FormType::CHECKBOX, ['required' => false]);
        }
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CustomerData::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
