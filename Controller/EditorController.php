<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EditorController extends Controller {

  public function getCollection() {
    return $this->container->get('server_grove_translation_editor.storage_manager')->getCollection();
  }

  public function listAction() {
    $data = $this->getCollection()->find();

    $data->sort(array('locale' => 1));

    $locales = array();
    $missing = array();

    // $this->getRequest()->attributes->get('_locale', 'en'); // session
    $default = $this->container->getParameter('locale', 'en');

    foreach($data as $d) {
      if(!isset($locales[$d['locale']])) {
        $locales[$d['locale']] = array(
          'translations' => array(),
          'data'    => array()
       );
      }
      if(is_array($d['translations'])) {
        $locales[$d['locale']]['translations'] = array_merge($locales[$d['locale']]['translations'], $d['translations']);
        $locales[$d['locale']]['data'][$d['filename']] = $d;
      }
    }

    $keys = array_keys($locales);

    foreach($keys as $locale) {
      if($locale != $default) {
        foreach($locales[$default]['translations'] as $key => $val) {
          if(!isset($locales[$locale]['translations'][$key])|| $locales[$locale]['translations'][$key] == $key) {
            $missing[$key] = 1;
          }
        }
      }
    }
    return $this->render('ServerGroveTranslationEditorBundle:Editor:list.html.twig', array(
        'locales' => $locales,
        'default' => $default,
        'missing' => $missing,
     )
   );
  }

  public function removeAction() {
    $request = $this->getRequest();

    if($request->isXmlHttpRequest()) {
      $key = $request->request->get('key');

      $values = $this->getCollection()->find();

      foreach($values as $data) {
        if(isset($data['translations'][$key])) {
          unset($data['translations'][$key]);
          $this->updateData($data);
        }
      }

      $res = array(
        'result' => true,
     );
      return new Response(json_encode($res));
    }
  }

  public function addAction() {
    $request = $this->getRequest();

    $locales = $request->request->get('locale');
    $key = $request->request->get('key');

    foreach($locales as $locale => $val) {
      $values = $this->getCollection()->find(array('locale' => $locale));
      $values = iterator_to_array($values);
      if(!count($values)) {
        continue;
      }
      $found = false;
      foreach($values as $data) {
        if(isset($data['translations'][$key])) {
          $res = array(
            'result' => false,
            'msg' => 'The key already exists. Please update it instead.',
         );
          return new Response(json_encode($res));
        }
      }

      $data = array_pop($values);

      $data['translations'][$key] = $val;

      if(!$request->request->get('check-only')) {
        $this->updateData($data);
      }
    }
    if($request->isXmlHttpRequest()) {
      $res = array(
        'result' => true,
     );
      return new Response(json_encode($res));
    }

    return new RedirectResponse($this->generateUrl('sg_localeditor_list'));
  }

  public function updateAction() {
    $request = $this->getRequest();

    if($request->isXmlHttpRequest()) {
      $locale = $request->request->get('locale');
      $key = $request->request->get('key');
      $val = $request->request->get('val');

      $values = $this->getCollection()->find(array('locale' => $locale));
      $values = iterator_to_array($values);

      $found = false;
      foreach($values as $data) {
        if(isset($data['translations'][$key])) {
          $found = true;
          break;
        }
      }
      if(!$found) {
        $data = array_pop($values);
      }

      $data['translations'][$key] = $val;
      $this->updateData($data);

      $res = array(
        'result' => true,
        'oldata' => $data['translations'][$key],

     );
      return new Response(json_encode($res));
    }
  }

  protected function updateData($data) {
    $this->getCollection()->update(
      array('_id' => $data['_id'])
      , $data, array('upsert' => true));
  }
}
