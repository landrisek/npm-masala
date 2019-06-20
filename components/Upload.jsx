import React from 'react'
import Dropzone from 'react-dropzone'

export class Upload extends React.Component {
    onClickUpload(props, state) {
        let files = this.state[props.id]
        delete files[state]
        this.setState({[props.id]:files})
    }
    onDropUpload(props, files) {
        let self = this
        for(let key in files) {
            var file = files[key]
            delete files[key]
            break
        }
        if(file.type.match('image')) {
            let reader = new FileReader()
            reader.onload = function() {
                fetch(props.link, {body: reader.result, method: 'POST'}).then(response => response.json()).then(state => {
                    self.OnDropUpload(props, files, state, file)
                })
            }
            reader.readAsDataURL(file)
        } else {
            fetch(props.link, {body: file, method: 'POST'}).then(response => response.json()).then(state => { self.OnDropUpload(props, state, files, file) })
        }
    }
    OnDropUpload(props, state, files, file) {
        if(undefined == this.state[props.id]) {
            this.state[props.id] = [file.name]
        } else {
            this.state[props.id].push(file.name)
        }
        fetch(props.onSuccess, {body:  JSON.stringify(this.state), method: 'POST'}).then(response => response.json()).then(state => {
            this.setState(state)
            if(Object.keys(files).length > 0) {
                this.onDropUpload(props, files)
            }
        })
    }
    Upload(props, state) {
        let files = []
        if(state) {
            for(let file in state) {
                files.push(<li className={'list-group-item'} key={props.id + file}>{state[file]}
                    <button aria-label={'Close'} className={'close'} onClick={this.onClickUpload.bind(this, props, file)} type={'button'}>
                        <span aria-hidden={'true'}>&times;</span>
                    </button>
                </li>)
            }
        }
        return <div key={props.id}>
            <center><Drop data={props} multiple={true} onDrop={this.onDropUpload.bind(this, props)} /></center>
            <ul className={'list-group'}>{files}</ul>
        </div>
    }
}

export class Drop extends React.Component {
    constructor(props) {
        super(props)
    }
    render() {
        return (
          <Dropzone onDrop={this.props.onDrop}>
            {({getRootProps, getInputProps}) => {
              return (
                <div {...getRootProps()} 
                    className={'dropzone'} 
                    style={{borderWidth:'2px',borderColor:'rgb(102, 102, 102)',borderStyle:'dashed',borderRadius:'5px',height:'100px',widht:'100%'}}>
                  <input {...getInputProps()} />
                  <p>{this.props.data.label}</p>
                </div>
              )
            }}
          </Dropzone>
        );
  }
}