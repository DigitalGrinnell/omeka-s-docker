<?php
namespace CustomVocab\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\User;

/**
 * @Entity
 */
class CustomVocab extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column(unique=true, length=190)
     */
    protected $label;

    /**
     * @Column(nullable=true, length=190)
     */
    protected $lang;

    /**
     * @Column(type="text")
     */
    protected $terms;

    /**
     * @ManyToOne(targetEntity="Omeka\Entity\User")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $owner;

    public function getId()
    {
        return $this->id;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function setTerms($terms)
    {
        $this->terms = $terms;
    }

    public function getTerms()
    {
        return $this->terms;
    }

    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }
}
