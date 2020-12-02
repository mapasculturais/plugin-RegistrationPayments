<?php

namespace RegistrationPayments;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use MapasCulturais\App;
use MapasCulturais\Traits;

/**
 * Payment
 *
 * @property-read int $id
 * 
 * @property MapasCulturais\Entities\Registration $registration
 * @property MapasCulturais\Entities\Opportunity $opportunity
 * @property float $amount
 * @property DateTime $paymentDate 
 * @property object $metadata 
 * @property MapasCulturais\Entities\User $createdByUser 
 * @property DateTime $createTimestamp 
 * @property DateTime $updateTimestamp 
 * @property int $status 
 * 
 * 
 * @ORM\Table(name="payment")
 * @ORM\Entity
 * @ORM\entity(repositoryClass="MapasCulturais\Repository")
 */
class Payment extends \MapasCulturais\Entity {
    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_FAILED = 2;
    const STATUS_EXPORTED = 3;
    const STATUS_AVAILABLE = 8;
    const STATUS_PAID = 10;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="payment_id_seq", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var \MapasCulturais\Entities\Registration
     *
     * @ORM\ManyToOne(targetEntity="MapasCulturais\Entities\Registration")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="registration_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * })
     */
    protected $registration;


    /**
     * @var \MapasCulturais\Entities\Opportunity
     *
     * @ORM\ManyToOne(targetEntity="MapasCulturais\Entities\Opportunity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="opportunity_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * })
     */
    protected $opportunity;

    /**
     * @var string|object
     *
     * @ORM\Column(name="amount", type="float", nullable=false)
     */
    protected $amount;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="payment_date", type="date", nullable=false)
     */
    protected $paymentDate;

    /**
     * @var object
     *
     * @ORM\Column(name="metadata", type="json_array", nullable=false)
     */
    protected $metadata;


    /**
     * @var \MapasCulturais\Entities\User
     *
     * @ORM\ManyToOne(targetEntity="MapasCulturais\Entities\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="created_by_user_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $createdByUser;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_timestamp", type="datetime", nullable=false)
     */
    protected $createTimestamp;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_timestamp", type="datetime", nullable=true)
     */
    protected $updateTimestamp;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint", nullable=false)
     */
    protected $status = self::STATUS_PENDING;

    function __construct()
    {
        parent::__construct();

        $app = App::i();

        if(!$this->metadata) {
            $this->metadata = (object) [];
        }
    }

    /**
     * @param DateTime|string $date Se string enviar no formato YYYY-MM-DD
     * @return void 
     */
    function setPaymentDate ($date) {
        if($date instanceof \DateTime) {
            $this->paymentDate = $date;
        } else if (is_string($date) && preg_match('#\d{4}-\d{2}-\d{2}#', $date)) { 
            $this->paymentDate = new \DateTime($date);
        } else {
            throw new \Exception('Wrong paymentDate format');
        }
    }

    function setRegistration(\MapasCulturais\Entities\Registration $registration) {
        $this->registration = $registration;
        $this->opportunity = $registration->opportunity;
    } 
}
