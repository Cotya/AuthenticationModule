<?php
/**
 *
 *
 *
 *
 */

namespace Cotya\Authentication\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerDataBuilder;

class Login extends \Magento\Framework\App\Action\Action
{

    /** @var AccountManagementInterface */
    protected $customerAccountManagement;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    protected $customerFactory;

    /** @var CustomerDataBuilder */
    protected $customerDataBuilder;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    
    /** @var Session */
    protected $session;
    
    /** @var ScopeConfigInterface  */
    protected $scopeConfig;

    public function __construct(
        Context $context,
        Session $customerSession,
        ScopeConfigInterface $scopeConfig,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory,
        AccountManagementInterface $customerAccountManagement
    ) {
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->session = $customerSession;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }
    
    
    public function execute()
    {
        if ($this->session->isLoggedIn()) {
            $this->_redirect('/');
            return;
        }
        
        $provider = new \League\OAuth2\Client\Provider\Github(array(
            'clientId'  => $this->scopeConfig->getValue(
                'cotya_authentication/github/client_id',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
            'clientSecret'  =>  $this->scopeConfig->getValue(
                'cotya_authentication/github/client_secret',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
            'redirectUri'   =>   $this->_url->getUrl('cotya_authentication/index/login'),
            'scopes' => array(),
        ));
        
        if (!$this->getRequest()->getParam('code')) {
            $authUrl = $provider->getAuthorizationUrl();
            $this->session->setData('oauth2state', $provider->state);
            $this->_redirect($authUrl);
            
        } elseif ($this->getRequest()->getParam('state') !== $this->session->getData('oauth2state')) {
            $this->session->unsetData('oauth2state');
        } else {
            $token = $provider->getAccessToken(
                'authorization_code',
                array( 'code' => $this->getRequest()->getParam('code'))
            );

            // Optional: Now you have a token you can look up a users profile data
            try {
                // We got an access token, let's now get the user's details
                $userDetails = $provider->getUserDetails($token);

                //var_dump($userDetails);
                // Use these details to create a new profile
                //printf('Hello %s!', $userDetails->firstName);


            } catch (\Exception $e) {
                //echo $e;
                // Failed to get user details
                //exit('Oh dear...');
                throw $e;
            }


            try {
                $customer = $this->customerRepository->get($userDetails->email);
            } catch (NoSuchEntityException $e) {
                /** @var \Magento\Customer\Model\Data\Customer $customerEntity */
                $customerEntity = $this->customerFactory->create();
                $customerEntity->setEmail($userDetails->email);
                $customerEntity->setFirstname($userDetails->nickname);
                $customerEntity->setLastname('Anon');
                $customer = $this->customerAccountManagement->createAccount($customerEntity);
            }
            
            
            /** @see \Magento\Customer\Controller\Account\LoginPost::execute */
            /** @see \Magento\Customer\Model\AccountManagement::authenticate */
            // @todo add confirmation validation
            // @todo maybe move some of this logic into the account manager
            //if ($customer->getConfirmation() && $this->isConfirmationRequired($customer)) {
            //    throw new EmailNotConfirmedException('This account is not confirmed.', []);
            //}
            
            $this->session->setCustomerDataAsLoggedIn($customer);
            $this->session->regenerateId();
            $this->_redirect('/');
            
            
            
            // Use this to interact with an API on the users behalf
            //echo $token->accessToken;

            // Use this to get a new access token if the old one expires
            //echo $token->refreshToken;

            // Number of seconds until the access token will expire, and need refreshing
            //echo $token->expires;
        }
        //die(__FILE__.":".__LINE__);
        
    }
}
