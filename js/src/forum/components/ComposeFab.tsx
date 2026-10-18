import Component from 'flarum/common/Component';
import Icon from 'flarum/common/components/Icon';
import { canStart, startDiscussion } from './compose';
import { t } from '../util';

/** The phone's floating "Start a discussion". */
export default class ComposeFab extends Component {
  view() {
    if (!canStart()) return null;

    return (
      <button type="button" className="MastheadFab" onclick={startDiscussion}>
        <Icon name="fas fa-plus" />
        <span>{t('compose')}</span>
      </button>
    );
  }
}
